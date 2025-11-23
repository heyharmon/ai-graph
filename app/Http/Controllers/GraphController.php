<?php

namespace App\Http\Controllers;

use App\Models\Graph;
use App\Models\Source;
use App\Services\Sitemap\SitemapCrawlerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GraphController extends Controller
{
    protected SitemapCrawlerService $crawler;

    public function __construct(SitemapCrawlerService $crawler)
    {
        $this->crawler = $crawler;
    }

    /**
     * Store a newly created graph and crawl sitemap
     */
    public function store(Request $request)
    {
        $request->validate([
            'website_url' => 'required|string|url|max:255',
        ]);

        $user = $request->user();
        $websiteUrl = $this->normalizeWebsite($request->website_url);

        if (!$websiteUrl) {
            throw ValidationException::withMessages([
                'website_url' => ['Invalid website URL provided.'],
            ]);
        }

        // Check if user already has a graph for this website
        $existingGraph = Graph::where('user_id', $user->id)
            ->where('website_url', $websiteUrl)
            ->first();

        if ($existingGraph) {
            return response()->json([
                'graph' => $existingGraph,
            ]);
        }

        try {
            // Crawl sitemap
            $sources = $this->crawler->crawl($websiteUrl);

            if (empty($sources)) {
                throw ValidationException::withMessages([
                    'website_url' => ['No sitemap found or no URLs discovered. Please ensure the website has a sitemap.xml file.'],
                ]);
            }

            // Create graph and sources in a transaction
            DB::beginTransaction();

            $graph = Graph::create([
                'user_id' => $user->id,
                'website_url' => $websiteUrl,
                'sources_count' => count($sources),
            ]);

            $sourcesToInsert = [];
            foreach ($sources as $source) {
                $sourcesToInsert[] = [
                    'graph_id' => $graph->id,
                    'url' => $source['url'],
                    'title' => $source['title'],
                    'status' => 'discovered',
                    'discovered_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert in chunks for better performance
            foreach (array_chunk($sourcesToInsert, 100) as $chunk) {
                Source::insert($chunk);
            }

            DB::commit();

            // Refresh to get updated sources_count
            $graph->refresh();

            return response()->json([
                'graph' => $graph,
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create graph', [
                'user_id' => $user->id,
                'website_url' => $websiteUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to crawl website. Please try again later.',
                'website_url' => ['Failed to crawl website. Please try again later.'],
            ], 422);
        }
    }

    /**
     * Display the specified graph
     */
    public function show(Request $request, Graph $graph)
    {
        // Ensure user owns this graph
        if ($graph->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'graph' => $graph,
        ]);
    }

    /**
     * List all graphs for the authenticated user
     */
    public function index(Request $request)
    {
        $graphs = Graph::where('user_id', $request->user()->id)
            ->withCount('sources')
            ->latest()
            ->get();

        return response()->json([
            'graphs' => $graphs,
        ]);
    }

    private function normalizeWebsite(?string $website): ?string
    {
        if (!$website) {
            return null;
        }

        $website = trim($website);
        if ($website === '') {
            return null;
        }

        if (!Str::startsWith($website, ['http://', 'https://'])) {
            $website = 'https://' . ltrim($website, '/');
        }

        return rtrim($website, '/');
    }
}

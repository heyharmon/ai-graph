<?php

namespace App\Http\Controllers;

use App\Models\Graph;
use App\Models\Page;
use App\Services\ExaService;
use App\Services\EntityExtractionService;
use App\Services\GraphBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GraphController extends Controller
{
    public function __construct(
        private ExaService $exaService,
        private EntityExtractionService $entityExtractionService,
        private GraphBuilderService $graphBuilderService
    ) {}

    public function index(Request $request)
    {
        $graphs = Graph::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($graphs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'website_url' => 'required|url',
            'name' => 'nullable|string|max:255',
        ]);

        $graph = Graph::create([
            'user_id' => $request->user()->id,
            'website_url' => $request->website_url,
            'name' => $request->name ?? parse_url($request->website_url, PHP_URL_HOST),
            'status' => 'pending',
        ]);

        // Process the graph asynchronously (but synchronously for now per requirements)
        try {
            $this->processGraph($graph);
        } catch (\Exception $e) {
            $graph->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('Graph processing failed', [
                'graph_id' => $graph->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to process graph',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json($graph->fresh(), 201);
    }

    public function show(Graph $graph)
    {
        // Load relationships
        $graph->load([
            'entities',
            'relationships.fromEntity',
            'relationships.toEntity',
            'pages',
        ]);

        return response()->json($graph);
    }

    public function update(Request $request, Graph $graph)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'reprocess' => 'nullable|boolean',
        ]);

        if ($request->has('name')) {
            $graph->update(['name' => $request->name]);
        }

        // Reprocess if requested
        if ($request->boolean('reprocess')) {
            // Clear existing data
            $graph->relationships()->delete();
            $graph->entities()->delete();
            $graph->pages()->delete();

            $graph->update(['status' => 'pending']);

            try {
                $this->processGraph($graph);
            } catch (\Exception $e) {
                $graph->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                return response()->json([
                    'message' => 'Failed to reprocess graph',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        return response()->json($graph->fresh());
    }

    public function destroy(Graph $graph)
    {
        $graph->delete();

        return response()->json(['message' => 'Graph deleted successfully']);
    }

    /**
     * Process a graph: crawl, extract entities, build relationships
     */
    private function processGraph(Graph $graph): void
    {
        $graph->update(['status' => 'crawling']);

        // Step 1: Crawl website
        $pages = $this->exaService->crawlWebsite($graph->website_url);

        // Store pages
        foreach ($pages as $pageData) {
            Page::create([
                'graph_id' => $graph->id,
                'url' => $pageData['url'],
                'title' => $pageData['title'],
                'content' => $pageData['content'],
                'page_type' => $pageData['page_type'],
                'crawled_at' => now(),
            ]);
        }

        $graph->update(['status' => 'processing']);

        // Step 2: Extract entities
        $entities = $this->entityExtractionService->extractEntities($pages);

        // Step 3: Build graph
        $this->graphBuilderService->buildGraph($graph, $entities);

        $graph->update(['status' => 'completed']);
    }
}

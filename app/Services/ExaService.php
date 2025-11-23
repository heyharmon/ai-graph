<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExaService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.exa.ai';

    public function __construct()
    {
        $this->apiKey = config('services.exa.api_key', env('EXA_API_KEY'));
    }

    /**
     * Crawl a website and return pages with content
     *
     * @param string $websiteUrl
     * @return array Array of page data with url, title, content, and page_type
     */
    public function crawlWebsite(string $websiteUrl): array
    {
        try {
            // Use Exa's search/content API to get website pages
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(300) // 5 minutes timeout
            ->post("{$this->baseUrl}/contents", [
                'urls' => [$websiteUrl],
                'text' => true,
                'use_cache' => false,
            ]);

            if (!$response->successful()) {
                Log::error('Exa API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to crawl website: ' . $response->body());
            }

            $data = $response->json();
            $pages = [];

            if (isset($data['results']) && is_array($data['results'])) {
                foreach ($data['results'] as $result) {
                    $pages[] = [
                        'url' => $result['url'] ?? '',
                        'title' => $result['title'] ?? '',
                        'content' => $result['text'] ?? '',
                        'page_type' => $this->identifyPageType($result['url'] ?? '', $result['title'] ?? ''),
                    ];
                }
            }

            // If we only got one page, try to get more pages from the site
            if (count($pages) <= 1) {
                $pages = array_merge($pages, $this->getAdditionalPages($websiteUrl));
            }

            return $pages;
        } catch (\Exception $e) {
            Log::error('ExaService crawl error', [
                'website' => $websiteUrl,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get additional pages from a website using Exa search
     */
    private function getAdditionalPages(string $websiteUrl): array
    {
        try {
            // Extract domain from URL
            $domain = parse_url($websiteUrl, PHP_URL_HOST);
            if (!$domain) {
                return [];
            }

            // Search for pages from this domain
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])
            ->timeout(300)
            ->post("{$this->baseUrl}/search", [
                'query' => "site:{$domain}",
                'num_results' => 50,
                'use_cache' => false,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $pages = [];

            if (isset($data['results']) && is_array($data['results'])) {
                foreach ($data['results'] as $result) {
                    // Get content for each URL
                    $contentResponse = Http::withHeaders([
                        'x-api-key' => $this->apiKey,
                    ])
                    ->timeout(60)
                    ->post("{$this->baseUrl}/v1/contents", [
                        'urls' => [$result['url'] ?? ''],
                        'text' => true,
                        'use_cache' => false,
                    ]);

                    if ($contentResponse->successful()) {
                        $contentData = $contentResponse->json();
                        if (isset($contentData['results'][0])) {
                            $page = $contentData['results'][0];
                            $pages[] = [
                                'url' => $page['url'] ?? '',
                                'title' => $page['title'] ?? '',
                                'content' => $page['text'] ?? '',
                                'page_type' => $this->identifyPageType($page['url'] ?? '', $page['title'] ?? ''),
                            ];
                        }
                    }
                }
            }

            return $pages;
        } catch (\Exception $e) {
            Log::error('ExaService getAdditionalPages error', [
                'website' => $websiteUrl,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Identify page type based on URL and title
     */
    private function identifyPageType(string $url, string $title): ?string
    {
        $urlLower = strtolower($url);
        $titleLower = strtolower($title);

        // Check URL patterns
        if (preg_match('/\/(service|services|product|products|offer|offering)/', $urlLower)) {
            return 'service';
        }
        if (preg_match('/\/(location|locations|city|cities|area|areas|region|regions)/', $urlLower)) {
            return 'location';
        }
        if (preg_match('/\/(about|company|team|contact)/', $urlLower)) {
            return 'about';
        }
        if (preg_match('/\/(blog|news|article|post)/', $urlLower)) {
            return 'blog';
        }

        // Check title patterns
        if (preg_match('/(service|product|offering|solution)/', $titleLower)) {
            return 'service';
        }
        if (preg_match('/(location|city|area|region|serving|coverage)/', $titleLower)) {
            return 'location';
        }

        return 'other';
    }
}


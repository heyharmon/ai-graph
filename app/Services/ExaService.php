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
        $this->apiKey = config('services.exa.api_key');
    }

    /**
     * Query Exa Answer API with a specific question about a website
     */
    public function answer(string $question, string $websiteUrl, array $options = []): ?string
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/answer", [
                'query' => $question,
                'contents' => [
                    [
                        'url' => $websiteUrl,
                    ],
                ],
                ...$options,
            ]);

            if ($response->successful()) {
                return $response->json()['answer'] ?? null;
            }

            Log::error('Exa API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exa API exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get prompt for extracting products/services
     */
    public function getProductsServicesPrompt(string $websiteUrl): string
    {
        return "Return an exhaustive list of products and services offered by this company with website {$websiteUrl}. Explore beyond this page for information. For each product or service, provide a brief description. Format your response as a JSON array of objects, each with 'name' and 'description' fields.";
    }

    /**
     * Get prompt for extracting locations
     */
    public function getLocationsPrompt(string $websiteUrl): string
    {
        return "Return an exhaustive list of geographic locations (cities, regions, states, countries) where this company operates or serves customers. The website is {$websiteUrl}. Explore beyond this page for information. Format your response as a JSON array of location names.";
    }

    /**
     * Get prompt for extracting customer types
     */
    public function getCustomerTypesPrompt(string $websiteUrl): string
    {
        return "Return an exhaustive list of target customer types, industries, personas, or market segments that this company serves. The website is {$websiteUrl}. Explore beyond this page for information. Format your response as a JSON array of objects, each with 'name' and optionally 'description' fields.";
    }

    /**
     * Get prompt for extracting attributes
     */
    public function getAttributesPrompt(string $websiteUrl): string
    {
        return "Return an exhaustive list of key characteristics, attributes, or features of this company's products/services, such as pricing models (free/paid), certifications, specializations, delivery methods, or other distinguishing features. The website is {$websiteUrl}. Explore beyond this page for information. Format your response as a JSON array of objects, each with 'name' and optionally 'description' fields.";
    }

    /**
     * Get prompt for extracting competitors
     */
    public function getCompetitorsPrompt(string $websiteUrl): string
    {
        return "Return an exhaustive list of competitors, alternative solutions, or similar companies mentioned or implied on this website: {$websiteUrl}. Explore beyond this page for information. Format your response as a JSON array of competitor names.";
    }

    /**
     * Get prompt for extracting product hierarchies (parent-child relationships)
     */
    public function getProductHierarchyPrompt(string $websiteUrl): string
    {
        return "Analyze the website {$websiteUrl} and identify hierarchical relationships between products/services. For example, 'auto loan refinance' is a child/subcategory of 'auto loan', or 'premium plan' is a tier of 'subscription service'. Return a JSON array of objects, each with 'parent' (the parent product/service name) and 'child' (the child/subcategory product/service name) fields. Only include relationships where one product is clearly a subcategory, variant, or tier of another.";
    }

    /**
     * Extract all entity types from a website
     */
    public function extractAllEntities(string $websiteUrl): array
    {
        $results = [
            'products_services' => [],
            'locations' => [],
            'customer_types' => [],
            'attributes' => [],
            'competitors' => [],
            'product_hierarchies' => [],
        ];

        // Extract products/services
        $answer = $this->answer($this->getProductsServicesPrompt($websiteUrl), $websiteUrl);
        if ($answer) {
            $results['products_services'] = $this->parseJsonResponse($answer);
        }

        // Extract product hierarchies (parent-child relationships)
        $answer = $this->answer($this->getProductHierarchyPrompt($websiteUrl), $websiteUrl);
        if ($answer) {
            $results['product_hierarchies'] = $this->parseJsonResponse($answer);
        }

        // Extract locations
        $answer = $this->answer($this->getLocationsPrompt($websiteUrl), $websiteUrl);
        if ($answer) {
            $results['locations'] = $this->parseJsonResponse($answer);
        }

        // Extract customer types
        $answer = $this->answer($this->getCustomerTypesPrompt($websiteUrl), $websiteUrl);
        if ($answer) {
            $results['customer_types'] = $this->parseJsonResponse($answer);
        }

        // Extract attributes
        $answer = $this->answer($this->getAttributesPrompt($websiteUrl), $websiteUrl);
        if ($answer) {
            $results['attributes'] = $this->parseJsonResponse($answer);
        }

        // Extract competitors
        $answer = $this->answer($this->getCompetitorsPrompt($websiteUrl), $websiteUrl);
        if ($answer) {
            $results['competitors'] = $this->parseJsonResponse($answer);
        }

        return $results;
    }

    /**
     * Parse JSON response from Exa, handling various formats
     */
    private function parseJsonResponse(string $response): array
    {
        // Try to extract JSON from the response
        // Exa might return JSON wrapped in markdown or text
        $jsonMatch = [];
        if (preg_match('/\[.*\]/s', $response, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json ?? [];
            }
        }

        // Try parsing the entire response as JSON
        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json ?? [];
        }

        // Fallback: try to extract structured data from text
        return $this->parseTextResponse($response);
    }

    /**
     * Fallback parser for text responses
     */
    private function parseTextResponse(string $response): array
    {
        $items = [];
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Try to extract list items
            if (preg_match('/^[-*•]\s*(.+)$/', $line, $matches)) {
                $items[] = ['name' => $matches[1]];
            } elseif (preg_match('/^\d+\.\s*(.+)$/', $line, $matches)) {
                $items[] = ['name' => $matches[1]];
            } elseif (!empty($line)) {
                $items[] = ['name' => $line];
            }
        }

        return $items;
    }
}


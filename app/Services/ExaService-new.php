<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ExaService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.exa.ai';

    public function __construct()
    {
        $this->apiKey = config('services.exa.key');

        if (empty($this->apiKey)) {
            throw new \Exception('Exa API key is not configured. Please set EXA_API_KEY in your .env file.');
        }
    }

    /**
     * Get an LLM answer to a question informed by Exa search results
     *
     * @param string $query The question or query to answer
     * @param array $options Optional configuration:
     *   - text: bool (default: true) - If true, includes full text content in search results
     *   - stream: bool (default: false) - If true, returns response as server-sent events stream
     * @return array{answer: string, citations: array, costDollars: array}
     * @throws \Exception
     */
    public function answer(string $query, array $options = []): array
    {
        $text = $options['text'] ?? true;
        $stream = $options['stream'] ?? false;

        $payload = [
            'query' => $query,
            'text' => $text,
            'stream' => $stream,
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/answer", $payload);

            if ($response->failed()) {
                throw new \Exception('Failed to get Exa answer: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            if (str_starts_with($e->getMessage(), 'Failed to get Exa answer:')) {
                throw $e;
            }
            throw new \Exception('Failed to generate Exa answer: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Search for content using Exa's search API
     *
     * @param string $query The search query
     * @param array $options Optional configuration:
     *   - text: bool (default: true) - If true, includes full text content in search results
     *   - num_results: int (default: 10) - Number of results to return
     *   - domain: string|null - Restrict search to a specific domain
     * @return array{results: array}
     * @throws \Exception
     */
    public function search(string $query, array $options = []): array
    {
        $text = $options['text'] ?? true;
        $numResults = $options['num_results'] ?? 10;
        $domain = $options['domain'] ?? null;

        $payload = [
            'query' => $query,
            'num_results' => $numResults,
            'text' => $text ? ['include_html_tags'] : null,
        ];

        // Add domain filter if provided
        if ($domain) {
            $payload['filters'] = [
                'domain' => $domain,
            ];
        }

        // Remove null values
        $payload = array_filter($payload, fn($value) => $value !== null);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/search", $payload);

            if ($response->failed()) {
                throw new \Exception('Failed to perform Exa search: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            if (str_starts_with($e->getMessage(), 'Failed to perform Exa search:')) {
                throw $e;
            }
            throw new \Exception('Failed to perform Exa search: ' . $e->getMessage(), 0, $e);
        }
    }
}

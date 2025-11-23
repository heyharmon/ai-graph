<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EntityExtractionService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
    }

    /**
     * Extract entities from website pages using OpenAI
     *
     * @param array $pages Array of pages with url, title, content
     * @return array Extracted entities grouped by type
     */
    public function extractEntities(array $pages): array
    {
        try {
            // Combine all page content for analysis
            $combinedContent = $this->combinePageContent($pages);

            // Use OpenAI to extract entities
            $prompt = $this->buildExtractionPrompt($combinedContent);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(300) // 5 minutes timeout
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert at analyzing business websites and extracting structured information about products, services, locations, customer types, attributes, and competitors. Always respond with valid JSON.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.3,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to extract entities: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '{}';
            $entities = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from OpenAI: ' . json_last_error_msg());
            }

            // Map source URLs to entities
            return $this->mapSourceUrls($entities, $pages);
        } catch (\Exception $e) {
            Log::error('EntityExtractionService error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Combine page content for analysis
     */
    private function combinePageContent(array $pages): string
    {
        $combined = [];
        foreach ($pages as $page) {
            $combined[] = "URL: {$page['url']}\nTitle: {$page['title']}\nContent: {$page['content']}\n";
        }
        return implode("\n---\n\n", $combined);
    }

    /**
     * Build the extraction prompt for OpenAI
     */
    private function buildExtractionPrompt(string $content): string
    {
        return <<<PROMPT
Analyze the following website content and extract all business entities. Return a JSON object with the following structure:

{
  "products_services": [
    {
      "name": "Service name",
      "attributes": ["attribute1", "attribute2"],
      "source_urls": ["url1", "url2"]
    }
  ],
  "locations": [
    {
      "name": "Location name",
      "attributes": ["attribute1"],
      "source_urls": ["url1"]
    }
  ],
  "customer_types": [
    {
      "name": "Customer type name",
      "attributes": ["attribute1"],
      "source_urls": ["url1"]
    }
  ],
  "attributes": [
    {
      "name": "Attribute name",
      "source_urls": ["url1"]
    }
  ],
  "competitors": [
    {
      "name": "Competitor name",
      "source_urls": ["url1"]
    }
  ]
}

Extract:
- Products/Services: What the business offers (e.g., "Emergency Plumbing", "24/7 HVAC Service")
- Locations: Geographic areas served (cities, regions, states) - be specific (e.g., "Denver, CO" not just "Denver")
- Customer Types: Target audiences, industries, personas (e.g., "Small Businesses", "Homeowners", "Restaurants")
- Attributes: Key characteristics (e.g., "24/7", "Certified", "Free Consultation", "Emergency")
- Competitors: Mentioned or implied competitive companies

Include source URLs where each entity was found. Be thorough but avoid duplicates.

Website Content:
{$content}
PROMPT;
    }

    /**
     * Map source URLs to entities based on which pages mention them
     */
    private function mapSourceUrls(array $entities, array $pages): array
    {
        // This is a simplified version - in a real implementation, you might want
        // to do more sophisticated matching to ensure source URLs are accurate
        return $entities;
    }
}


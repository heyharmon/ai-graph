<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Graph;
use App\Models\Relationship;
use App\Models\SourcePage;
use Illuminate\Support\Facades\Log;

class EntityExtractionService
{
    public function __construct(
        private ExaService $exaService
    ) {
    }

    /**
     * Extract and store entities for a graph
     */
    public function extractEntities(Graph $graph): void
    {
        $graph->update(['status' => 'processing']);

        try {
            $websiteUrl = $graph->website_url;
            $extracted = $this->exaService->extractAllEntities($websiteUrl);

            // Store entities
            $entityMap = [];
            $entityMap['product_service'] = $this->storeEntities($graph, 'product_service', $extracted['products_services'] ?? []);
            $entityMap['location'] = $this->storeEntities($graph, 'location', $extracted['locations'] ?? []);
            $entityMap['customer_type'] = $this->storeEntities($graph, 'customer_type', $extracted['customer_types'] ?? []);
            $entityMap['attribute'] = $this->storeEntities($graph, 'attribute', $extracted['attributes'] ?? []);
            $entityMap['competitor'] = $this->storeEntities($graph, 'competitor', $extracted['competitors'] ?? []);

            // Map relationships
            $this->mapProductHierarchies($graph, $entityMap['product_service'], $extracted['product_hierarchies'] ?? []);
            // Note: Other relationship types (locations, customer types, etc.) are temporarily disabled
            // until we implement proper semantic relationship extraction
            // $this->mapRelationships($graph, $entityMap);

            // Store source page
            $this->storeSourcePage($graph, $websiteUrl);

            $graph->update([
                'status' => 'completed',
                'metadata' => [
                    'extracted_at' => now()->toIso8601String(),
                    'entity_counts' => array_map(fn($entities) => count($entities), $entityMap),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Entity extraction failed', [
                'graph_id' => $graph->id,
                'error' => $e->getMessage(),
            ]);

            $graph->update([
                'status' => 'failed',
                'metadata' => [
                    'error' => $e->getMessage(),
                ],
            ]);

            throw $e;
        }
    }

    /**
     * Store entities of a specific type
     */
    private function storeEntities(Graph $graph, string $type, array $items): array
    {
        $entities = [];

        foreach ($items as $item) {
            $name = is_array($item) ? ($item['name'] ?? $item[0] ?? '') : $item;
            $description = is_array($item) ? ($item['description'] ?? null) : null;

            if (empty($name)) {
                continue;
            }

            // Check if entity already exists
            $entity = Entity::where('graph_id', $graph->id)
                ->where('type', $type)
                ->where('name', $name)
                ->first();

            if (!$entity) {
                $entity = Entity::create([
                    'graph_id' => $graph->id,
                    'type' => $type,
                    'name' => $name,
                    'description' => $description,
                    'attributes' => is_array($item) ? $item : null,
                ]);
            }

            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * Map product hierarchies (parent-child relationships)
     */
    private function mapProductHierarchies(Graph $graph, array $products, array $hierarchies): void
    {
        // Create a lookup map by name for quick entity finding
        $productMap = [];
        foreach ($products as $product) {
            $productMap[strtolower(trim($product->name))] = $product;
        }

        foreach ($hierarchies as $hierarchy) {
            $parentName = is_array($hierarchy) ? ($hierarchy['parent'] ?? $hierarchy[0] ?? '') : '';
            $childName = is_array($hierarchy) ? ($hierarchy['child'] ?? $hierarchy[1] ?? '') : '';

            if (empty($parentName) || empty($childName)) {
                continue;
            }

            // Find parent and child entities by name (case-insensitive)
            $parent = $productMap[strtolower(trim($parentName))] ?? null;
            $child = $productMap[strtolower(trim($childName))] ?? null;

            if ($parent && $child && $parent->id !== $child->id) {
                // Create parent-child relationship: parent -> child
                $this->createRelationship($graph, $parent, $child, 'has_child');
            }
        }
    }

    /**
     * Map relationships between entities
     * NOTE: This is temporarily disabled - we need to implement semantic relationship extraction
     * instead of all-to-all connections
     */
    private function mapRelationships(Graph $graph, array $entityMap): void
    {
        // Services offered in locations
        foreach ($entityMap['product_service'] as $service) {
            foreach ($entityMap['location'] as $location) {
                $this->createRelationship($graph, $service, $location, 'offered_in');
            }
        }

        // Customer types use services
        foreach ($entityMap['customer_type'] as $customerType) {
            foreach ($entityMap['product_service'] as $service) {
                $this->createRelationship($graph, $customerType, $service, 'used_by');
            }
        }

        // Attributes apply to products/services
        foreach ($entityMap['attribute'] as $attribute) {
            foreach ($entityMap['product_service'] as $service) {
                $this->createRelationship($graph, $attribute, $service, 'has_attribute');
            }
        }

        // Locations grouped into regions (simplified - could be enhanced)
        // For now, we'll skip this as it requires more sophisticated logic

        // Competitors compete with services
        foreach ($entityMap['competitor'] as $competitor) {
            foreach ($entityMap['product_service'] as $service) {
                $this->createRelationship($graph, $competitor, $service, 'competes_with');
            }
        }
    }

    /**
     * Create a relationship if it doesn't exist
     */
    private function createRelationship(Graph $graph, Entity $source, Entity $target, string $type): void
    {
        $exists = Relationship::where('graph_id', $graph->id)
            ->where('source_entity_id', $source->id)
            ->where('target_entity_id', $target->id)
            ->where('relationship_type', $type)
            ->exists();

        if (!$exists) {
            Relationship::create([
                'graph_id' => $graph->id,
                'source_entity_id' => $source->id,
                'target_entity_id' => $target->id,
                'relationship_type' => $type,
            ]);
        }
    }

    /**
     * Store source page information
     */
    private function storeSourcePage(Graph $graph, string $url): void
    {
        SourcePage::firstOrCreate(
            [
                'graph_id' => $graph->id,
                'url' => $url,
            ],
            [
                'title' => null,
                'content_snippet' => null,
            ]
        );
    }
}


<?php

namespace App\Services;

use App\Models\Graph;
use App\Models\Entity;
use App\Models\Relationship;
use Illuminate\Support\Facades\Log;

class GraphBuilderService
{
    /**
     * Build the knowledge graph from extracted entities
     *
     * @param Graph $graph
     * @param array $entities Extracted entities from EntityExtractionService
     * @return void
     */
    public function buildGraph(Graph $graph, array $entities): void
    {
        try {
            // First, create all entities
            $entityMap = $this->createEntities($graph, $entities);

            // Then, create relationships
            $this->createRelationships($graph, $entities, $entityMap);
        } catch (\Exception $e) {
            Log::error('GraphBuilderService error', [
                'graph_id' => $graph->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create entity records in the database
     *
     * @param Graph $graph
     * @param array $entities
     * @return array Map of entity name to Entity model
     */
    private function createEntities(Graph $graph, array $entities): array
    {
        $entityMap = [];

        $typeMap = [
            'products_services' => 'product_service',
            'locations' => 'location',
            'customer_types' => 'customer_type',
            'attributes' => 'attribute',
            'competitors' => 'competitor',
        ];

        foreach ($typeMap as $key => $dbType) {
            if (!isset($entities[$key]) || !is_array($entities[$key])) {
                continue;
            }

            foreach ($entities[$key] as $entityData) {
                $name = $entityData['name'] ?? '';
                if (empty($name)) {
                    continue;
                }

                // Check if entity already exists (avoid duplicates)
                $entity = Entity::where('graph_id', $graph->id)
                    ->where('type', $dbType)
                    ->where('name', $name)
                    ->first();

                if (!$entity) {
                    $entity = Entity::create([
                        'graph_id' => $graph->id,
                        'type' => $dbType,
                        'name' => $name,
                        'attributes' => $entityData['attributes'] ?? [],
                        'metadata' => [
                            'source_urls' => $entityData['source_urls'] ?? [],
                        ],
                    ]);
                }

                $entityMap[$dbType . ':' . $name] = $entity;
            }
        }

        return $entityMap;
    }

    /**
     * Create relationship records between entities
     *
     * @param Graph $graph
     * @param array $entities
     * @param array $entityMap
     * @return void
     */
    private function createRelationships(Graph $graph, array $entities, array $entityMap): void
    {
        // Find products/services and their relationships
        if (isset($entities['products_services']) && is_array($entities['products_services'])) {
            foreach ($entities['products_services'] as $service) {
                $serviceName = $service['name'] ?? '';
                $serviceKey = 'product_service:' . $serviceName;
                $serviceEntity = $entityMap[$serviceKey] ?? null;

                if (!$serviceEntity) {
                    continue;
                }

                // Link services to locations (if mentioned together)
                if (isset($entities['locations']) && is_array($entities['locations'])) {
                    foreach ($entities['locations'] as $location) {
                        $locationName = $location['name'] ?? '';
                        $locationKey = 'location:' . $locationName;
                        $locationEntity = $entityMap[$locationKey] ?? null;

                        if ($locationEntity && $this->entitiesCoOccur($service, $location)) {
                            $this->createRelationship(
                                $graph,
                                $serviceEntity,
                                $locationEntity,
                                'offers_in',
                                array_merge(
                                    $service['source_urls'] ?? [],
                                    $location['source_urls'] ?? []
                                )
                            );
                        }
                    }
                }

                // Link services to customer types
                if (isset($entities['customer_types']) && is_array($entities['customer_types'])) {
                    foreach ($entities['customer_types'] as $customerType) {
                        $customerName = $customerType['name'] ?? '';
                        $customerKey = 'customer_type:' . $customerName;
                        $customerEntity = $entityMap[$customerKey] ?? null;

                        if ($customerEntity && $this->entitiesCoOccur($service, $customerType)) {
                            $this->createRelationship(
                                $graph,
                                $customerEntity,
                                $serviceEntity,
                                'uses',
                                array_merge(
                                    $service['source_urls'] ?? [],
                                    $customerType['source_urls'] ?? []
                                )
                            );
                        }
                    }
                }

                // Link services to attributes
                if (isset($service['attributes']) && is_array($service['attributes'])) {
                    foreach ($service['attributes'] as $attrName) {
                        $attrKey = 'attribute:' . $attrName;
                        $attrEntity = $entityMap[$attrKey] ?? null;

                        if ($attrEntity) {
                            $this->createRelationship(
                                $graph,
                                $serviceEntity,
                                $attrEntity,
                                'has_attribute',
                                $service['source_urls'] ?? []
                            );
                        }
                    }
                }
            }
        }

        // Link locations to regions (if we can infer grouping)
        if (isset($entities['locations']) && is_array($entities['locations'])) {
            $this->linkLocationRegions($graph, $entities['locations'], $entityMap);
        }
    }

    /**
     * Check if two entities co-occur (mentioned on same pages)
     */
    private function entitiesCoOccur(array $entity1, array $entity2): bool
    {
        $urls1 = $entity1['source_urls'] ?? [];
        $urls2 = $entity2['source_urls'] ?? [];

        return !empty(array_intersect($urls1, $urls2));
    }

    /**
     * Create a relationship between two entities
     */
    private function createRelationship(
        Graph $graph,
        Entity $fromEntity,
        Entity $toEntity,
        string $type,
        array $sourceUrls
    ): void {
        // Check if relationship already exists
        $exists = Relationship::where('graph_id', $graph->id)
            ->where('from_entity_id', $fromEntity->id)
            ->where('to_entity_id', $toEntity->id)
            ->where('type', $type)
            ->exists();

        if (!$exists) {
            Relationship::create([
                'graph_id' => $graph->id,
                'from_entity_id' => $fromEntity->id,
                'to_entity_id' => $toEntity->id,
                'type' => $type,
                'source_urls' => array_unique($sourceUrls),
            ]);
        }
    }

    /**
     * Link locations to regions (e.g., cities to states)
     */
    private function linkLocationRegions(Graph $graph, array $locations, array $entityMap): void
    {
        // Simple heuristic: if a location contains a comma, the part after comma might be a region
        foreach ($locations as $location) {
            $locationName = $location['name'] ?? '';
            $locationKey = 'location:' . $locationName;
            $locationEntity = $entityMap[$locationKey] ?? null;

            if (!$locationEntity) {
                continue;
            }

            // Check if location name contains region info (e.g., "Denver, CO")
            if (strpos($locationName, ',') !== false) {
                $parts = explode(',', $locationName);
                $city = trim($parts[0]);
                $region = trim($parts[1] ?? '');

                if (!empty($region)) {
                    // Look for a region entity
                    $regionKey = 'location:' . $region;
                    $regionEntity = $entityMap[$regionKey] ?? null;

                    if ($regionEntity) {
                        $this->createRelationship(
                            $graph,
                            $locationEntity,
                            $regionEntity,
                            'grouped_into',
                            $location['source_urls'] ?? []
                        );
                    }
                }
            }
        }
    }
}


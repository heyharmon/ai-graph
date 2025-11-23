<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import * as d3 from 'd3'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import graphService from '@/services/graph'

const route = useRoute()
const router = useRouter()
const graphId = computed(() => parseInt(route.params.id))

const graph = ref(null)
const loading = ref(false)
const error = ref(null)
const searchQuery = ref('')
const selectedEntityType = ref('all')
const selectedNode = ref(null)

const entityTypeColors = {
    product_service: '#3b82f6', // blue
    location: '#10b981', // green
    customer_type: '#f59e0b', // amber
    attribute: '#8b5cf6', // purple
    competitor: '#ef4444', // red
}

const entityTypeLabels = {
    product_service: 'Products/Services',
    location: 'Locations',
    customer_type: 'Customer Types',
    attribute: 'Attributes',
    competitor: 'Competitors',
    all: 'All Types',
}

const fetchGraph = async () => {
    loading.value = true
    error.value = null
    try {
        graph.value = await graphService.show(graphId.value)
        if (graph.value.status === 'completed') {
            await renderGraph()
        }
    } catch (err) {
        error.value = err?.message || 'Failed to load graph.'
    } finally {
        loading.value = false
    }
}

const renderGraph = async () => {
    if (!graph.value || !graph.value.entities || graph.value.entities.length === 0) {
        return
    }

    // Clear existing SVG
    d3.select('#graph-container').selectAll('*').remove()

    const width = 1200
    const height = 800
    const svg = d3.select('#graph-container')
        .append('svg')
        .attr('width', width)
        .attr('height', height)

    // Get filtered entities based on current filters
    const entitiesToShow = filteredEntities.value.length > 0 
        ? filteredEntities.value 
        : graph.value.entities

    // Create a map of entity IDs to entities for quick lookup
    const entityMap = new Map()
    entitiesToShow.forEach(entity => {
        entityMap.set(entity.id, entity)
    })

    // Prepare relationships for D3 (only include relationships between visible entities)
    const links = (graph.value.relationships || []).map(rel => {
        const sourceEntity = entityMap.get(rel.from_entity_id)
        const targetEntity = entityMap.get(rel.to_entity_id)
        if (!sourceEntity || !targetEntity) return null
        return {
            source: sourceEntity,
            target: targetEntity,
            type: rel.type,
            strength: rel.strength || 1.0,
        }
    }).filter(link => link !== null)

    // Create force simulation
    const simulation = d3.forceSimulation(entitiesToShow)
        .force('link', d3.forceLink(links).id(d => d.id).distance(100))
        .force('charge', d3.forceManyBody().strength(-300))
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('collision', d3.forceCollide().radius(30))

    // Create links
    const link = svg.append('g')
        .attr('class', 'links')
        .selectAll('line')
        .data(links)
        .enter()
        .append('line')
        .attr('stroke', '#999')
        .attr('stroke-opacity', 0.6)
        .attr('stroke-width', d => Math.sqrt(d.strength || 1) * 2)

    // Create nodes
    const node = svg.append('g')
        .attr('class', 'nodes')
        .selectAll('circle')
        .data(entitiesToShow)
        .enter()
        .append('circle')
        .attr('r', 12)
        .attr('fill', d => entityTypeColors[d.type] || '#6b7280')
        .attr('stroke', '#fff')
        .attr('stroke-width', 2)
        .style('cursor', 'pointer')
        .call(drag(simulation))
        .on('click', (event, d) => {
            selectedNode.value = d
        })

    // Add labels
    const label = svg.append('g')
        .attr('class', 'labels')
        .selectAll('text')
        .data(entitiesToShow)
        .enter()
        .append('text')
        .text(d => d.name)
        .attr('font-size', '10px')
        .attr('dx', 15)
        .attr('dy', 4)
        .style('pointer-events', 'none')
        .style('user-select', 'none')

    // Update positions on simulation tick
    simulation.on('tick', () => {
        link
            .attr('x1', d => d.source.x)
            .attr('y1', d => d.source.y)
            .attr('x2', d => d.target.x)
            .attr('y2', d => d.target.y)

        node
            .attr('cx', d => d.x)
            .attr('cy', d => d.y)

        label
            .attr('x', d => d.x)
            .attr('y', d => d.y)
    })

    // Drag handler
    function drag(simulation) {
        function dragstarted(event) {
            if (!event.active) simulation.alphaTarget(0.3).restart()
            event.subject.fx = event.subject.x
            event.subject.fy = event.subject.y
        }

        function dragged(event) {
            event.subject.fx = event.x
            event.subject.fy = event.y
        }

        function dragended(event) {
            if (!event.active) simulation.alphaTarget(0)
            event.subject.fx = null
            event.subject.fy = null
        }

        return d3.drag()
            .on('start', dragstarted)
            .on('drag', dragged)
            .on('end', dragended)
    }
}

// Filter entities based on current filters
const filteredEntities = computed(() => {
    if (!graph.value || !graph.value.entities) return []
    
    let entities = graph.value.entities
    
    // Filter by type
    if (selectedEntityType.value !== 'all') {
        entities = entities.filter(e => e.type === selectedEntityType.value)
    }
    
    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        entities = entities.filter(e => 
            e.name.toLowerCase().includes(query)
        )
    }
    
    return entities
})

// Watch for filter changes and re-render graph
watch([searchQuery, selectedEntityType], () => {
    if (graph.value && graph.value.status === 'completed') {
        renderGraph()
    }
})

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        crawling: 'bg-blue-100 text-blue-800',
        processing: 'bg-purple-100 text-purple-800',
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
    }
    return colors[status] || 'bg-neutral-100 text-neutral-800'
}

const getEntityStats = computed(() => {
    if (!graph.value || !graph.value.entities) return {}
    
    const stats = {}
    graph.value.entities.forEach(entity => {
        stats[entity.type] = (stats[entity.type] || 0) + 1
    })
    return stats
})

onMounted(() => {
    fetchGraph()
    
    // Poll for status updates if graph is processing
    const interval = setInterval(() => {
        if (graph.value && ['pending', 'crawling', 'processing'].includes(graph.value.status)) {
            fetchGraph()
        } else {
            clearInterval(interval)
        }
    }, 3000) // Poll every 3 seconds
    
    return () => clearInterval(interval)
})
</script>

<template>
    <DefaultLayout>
        <div class="container mx-auto px-4 py-8">
            <div v-if="loading && !graph" class="flex items-center justify-center py-12">
                <div class="text-neutral-500">Loading graph...</div>
            </div>

            <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="text-red-800">{{ error }}</p>
            </div>

            <div v-else-if="graph">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-neutral-900">{{ graph.name }}</h1>
                        <p class="text-sm text-neutral-500">{{ graph.website_url }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            :class="[
                                'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                getStatusColor(graph.status)
                            ]"
                        >
                            {{ graph.status }}
                        </span>
                        <Button variant="outline" @click="router.push({ name: 'graphs.index' })">
                            Back to Graphs
                        </Button>
                    </div>
                </div>

                <!-- Status Messages -->
                <div v-if="graph.status === 'pending'" class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                    <p class="text-yellow-800">Graph creation is pending. Processing will begin shortly...</p>
                </div>
                <div v-else-if="graph.status === 'crawling'" class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <p class="text-blue-800">Crawling website... This may take a few minutes.</p>
                </div>
                <div v-else-if="graph.status === 'processing'" class="mb-6 rounded-lg border border-purple-200 bg-purple-50 p-4">
                    <p class="text-purple-800">Extracting entities and building relationships... This may take a few minutes.</p>
                </div>
                <div v-else-if="graph.status === 'failed'" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                    <p class="text-red-800">Graph processing failed: {{ graph.error_message || 'Unknown error' }}</p>
                </div>

                <!-- Graph Visualization -->
                <div v-if="graph.status === 'completed'" class="space-y-6">
                    <!-- Filters and Controls -->
                    <div class="rounded-lg border border-neutral-200 bg-white p-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="flex-1 min-w-[200px]">
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search entities..."
                                    type="search"
                                />
                            </div>
                            <div>
                                <select
                                    v-model="selectedEntityType"
                                    class="rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option v-for="(label, type) in entityTypeLabels" :key="type" :value="type">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                        <div
                            v-for="(count, type) in getEntityStats"
                            :key="type"
                            class="rounded-lg border border-neutral-200 bg-white p-4"
                        >
                            <div class="text-2xl font-bold" :style="{ color: entityTypeColors[type] }">
                                {{ count }}
                            </div>
                            <div class="text-xs text-neutral-500">{{ entityTypeLabels[type] }}</div>
                        </div>
                        <div class="rounded-lg border border-neutral-200 bg-white p-4">
                            <div class="text-2xl font-bold text-neutral-900">
                                {{ graph.relationships?.length || 0 }}
                            </div>
                            <div class="text-xs text-neutral-500">Relationships</div>
                        </div>
                    </div>

                    <!-- Graph Canvas -->
                    <div class="rounded-lg border border-neutral-200 bg-white p-4">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-neutral-900">Knowledge Graph</h2>
                            <div class="flex gap-2 text-xs text-neutral-500">
                                <span>Click nodes to explore</span>
                                <span>•</span>
                                <span>Drag to reposition</span>
                            </div>
                        </div>
                        <div
                            id="graph-container"
                            class="overflow-auto rounded border border-neutral-200 bg-neutral-50"
                            style="min-height: 800px;"
                        ></div>
                    </div>

                    <!-- Legend -->
                    <div class="rounded-lg border border-neutral-200 bg-white p-4">
                        <h3 class="mb-3 text-sm font-semibold text-neutral-900">Legend</h3>
                        <div class="flex flex-wrap gap-4">
                            <template v-for="(label, type) in entityTypeLabels" :key="type">
                                <div
                                    v-if="type !== 'all'"
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        class="h-4 w-4 rounded-full"
                                        :style="{ backgroundColor: entityTypeColors[type] }"
                                    ></div>
                                    <span class="text-sm text-neutral-600">{{ label }}</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Node Details Panel -->
                    <div v-if="selectedNode" class="rounded-lg border border-neutral-200 bg-white p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-neutral-900">Entity Details</h3>
                            <Button size="sm" variant="outline" @click="selectedNode = null">Close</Button>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <div class="text-sm font-medium text-neutral-700">Name</div>
                                <div class="text-neutral-900">{{ selectedNode.name }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-neutral-700">Type</div>
                                <div class="text-neutral-900">{{ entityTypeLabels[selectedNode.type] }}</div>
                            </div>
                            <div v-if="selectedNode.attributes && selectedNode.attributes.length > 0">
                                <div class="text-sm font-medium text-neutral-700">Attributes</div>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="attr in selectedNode.attributes"
                                        :key="attr"
                                        class="rounded-full bg-neutral-100 px-2 py-1 text-xs text-neutral-700"
                                    >
                                        {{ attr }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="selectedNode.metadata?.source_urls && selectedNode.metadata.source_urls.length > 0">
                                <div class="text-sm font-medium text-neutral-700">Source URLs</div>
                                <ul class="list-disc list-inside space-y-1 text-sm text-neutral-600">
                                    <li v-for="url in selectedNode.metadata.source_urls.slice(0, 5)" :key="url">
                                        <a :href="url" target="_blank" class="text-blue-600 hover:underline">
                                            {{ url }}
                                        </a>
                                    </li>
                                    <li v-if="selectedNode.metadata.source_urls.length > 5" class="text-neutral-500">
                                        +{{ selectedNode.metadata.source_urls.length - 5 }} more
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>


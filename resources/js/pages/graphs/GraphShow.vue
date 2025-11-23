<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { VueFlow } from '@vue-flow/core'
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Button from '@/components/ui/Button.vue'
import graphService from '@/services/graph'

const route = useRoute()
const router = useRouter()
const graphId = computed(() => route.params.id)

const graph = ref(null)
const entities = ref([])
const relationships = ref([])
const loading = ref(false)
const error = ref(null)
const selectedEntity = ref(null)

const initialNodes = ref([])
const initialEdges = ref([])

const updateGraphData = () => {
    if (!entities.value.length) {
        initialNodes.value = []
        initialEdges.value = []
        return
    }

    const nodeMap = {}
    const typeColors = {
        product_service: '#3b82f6',
        location: '#10b981',
        customer_type: '#f59e0b',
        attribute: '#8b5cf6',
        competitor: '#ef4444'
    }

    entities.value.forEach(entity => {
        nodeMap[entity.id] = {
            id: String(entity.id),
            type: 'default',
            label: entity.name,
            data: { entity },
            position: { x: Math.random() * 800, y: Math.random() * 600 },
            style: {
                background: typeColors[entity.type] || '#6b7280',
                color: '#fff',
                border: '2px solid #fff',
                borderRadius: '8px',
                padding: '10px',
                fontSize: '12px',
                fontWeight: '600'
            }
        }
    })

    initialNodes.value = Object.values(nodeMap)

    initialEdges.value = relationships.value.map(rel => ({
        id: `e${rel.source_entity_id}-${rel.target_entity_id}-${rel.relationship_type}`,
        source: String(rel.source_entity_id),
        target: String(rel.target_entity_id),
        label: rel.relationship_type.replace('_', ' '),
        type: 'smoothstep',
        animated: true,
        style: { stroke: '#6b7280', strokeWidth: 2 }
    }))
}

const fetchGraph = async () => {
    loading.value = true
    error.value = null
    try {
        graph.value = await graphService.show(graphId.value)
        entities.value = graph.value.entities || []
        relationships.value = graph.value.relationships || []
        updateGraphData()
    } catch (err) {
        error.value = err?.message || 'Failed to load graph'
    } finally {
        loading.value = false
    }
}

const handleExtract = async () => {
    if (!confirm('This will re-extract entities from the website. Continue?')) {
        return
    }

    loading.value = true
    try {
        await graphService.extract(graphId.value)
        await fetchGraph()
    } catch (err) {
        error.value = err?.message || 'Failed to extract entities'
    } finally {
        loading.value = false
    }
}

const onNodeClick = ({ node }) => {
    selectedEntity.value = node.data.entity
}

const onEdgeClick = () => {
    selectedEntity.value = null
}

onMounted(() => {
    fetchGraph()
    // Poll for updates if graph is processing
    const interval = setInterval(() => {
        if (graph.value?.status === 'processing' || graph.value?.status === 'pending') {
            fetchGraph()
        } else {
            clearInterval(interval)
        }
    }, 3000)

    return () => clearInterval(interval)
})

const entityTypeCounts = computed(() => {
    const counts = {}
    entities.value.forEach(entity => {
        counts[entity.type] = (counts[entity.type] || 0) + 1
    })
    return counts
})

const formatEntityType = (type) => {
    return type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}
</script>

<template>
    <DefaultLayout>
        <div class="container mx-auto px-4 py-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900">Knowledge Graph</h1>
                    <p v-if="graph" class="text-sm text-neutral-500 mt-1">{{ graph.website_url }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="outline" @click="router.push({ name: 'graphs.index' })">
                        Back to Graphs
                    </Button>
                    <Button v-if="graph" @click="handleExtract" :disabled="loading">
                        Re-extract
                    </Button>
                </div>
            </div>

            <div v-if="error" class="mb-4 rounded-md bg-red-50 p-3">
                <p class="text-sm text-red-800">{{ error }}</p>
            </div>

            <div v-if="loading && !graph" class="flex items-center justify-center py-12">
                <div class="text-sm text-neutral-500">Loading graph...</div>
            </div>

            <div v-else-if="graph" class="space-y-4">
                <!-- Status Banner -->
                <div v-if="graph.status === 'processing' || graph.status === 'pending'" class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-center gap-3">
                        <div class="h-2 w-2 animate-pulse rounded-full bg-blue-600"></div>
                        <div>
                            <p class="text-sm font-medium text-blue-900">Processing...</p>
                            <p class="text-xs text-blue-700">Extracting entities from website. This may take a few minutes.</p>
                        </div>
                    </div>
                </div>

                <div v-if="graph.status === 'failed'" class="rounded-lg border border-red-200 bg-red-50 p-4">
                    <p class="text-sm font-medium text-red-900">Extraction failed</p>
                    <p class="text-xs text-red-700 mt-1">{{ graph.metadata?.error || 'An error occurred during extraction' }}</p>
                </div>

                <!-- Stats -->
                <div v-if="graph.status === 'completed'" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="rounded-lg border border-neutral-200 bg-white p-4">
                        <div class="text-2xl font-bold text-neutral-900">{{ entities.length }}</div>
                        <div class="text-xs text-neutral-500 mt-1">Total Entities</div>
                    </div>
                    <div class="rounded-lg border border-neutral-200 bg-white p-4">
                        <div class="text-2xl font-bold text-neutral-900">{{ relationships.length }}</div>
                        <div class="text-xs text-neutral-500 mt-1">Relationships</div>
                    </div>
                    <div class="rounded-lg border border-neutral-200 bg-white p-4">
                        <div class="text-2xl font-bold text-neutral-900">{{ Object.keys(entityTypeCounts).length }}</div>
                        <div class="text-xs text-neutral-500 mt-1">Entity Types</div>
                    </div>
                    <div class="rounded-lg border border-neutral-200 bg-white p-4">
                        <div class="text-2xl font-bold text-neutral-900">{{ graph.source_pages?.length || 0 }}</div>
                        <div class="text-xs text-neutral-500 mt-1">Source Pages</div>
                    </div>
                </div>

                <!-- Entity Type Breakdown -->
                <div v-if="graph.status === 'completed' && Object.keys(entityTypeCounts).length > 0" class="rounded-lg border border-neutral-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-neutral-900 mb-3">Entity Types</h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div v-for="(count, type) in entityTypeCounts" :key="type" class="text-center">
                            <div class="text-lg font-bold text-neutral-900">{{ count }}</div>
                            <div class="text-xs text-neutral-500 mt-1">{{ formatEntityType(type) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Graph Visualization -->
                <div v-if="graph.status === 'completed'" class="rounded-lg border border-neutral-200 bg-white overflow-hidden" style="height: 600px;">
                    <VueFlow 
                        :nodes="initialNodes" 
                        :edges="initialEdges" 
                        class="vue-flow-container"
                        @node-click="onNodeClick"
                        @edge-click="onEdgeClick"
                    >
                        <template #node-default="{ data }">
                            <div class="px-3 py-2 rounded-lg shadow-sm cursor-pointer" :style="{ background: data.entity.type === 'product_service' ? '#3b82f6' : data.entity.type === 'location' ? '#10b981' : data.entity.type === 'customer_type' ? '#f59e0b' : data.entity.type === 'attribute' ? '#8b5cf6' : '#ef4444', color: '#fff' }">
                                <div class="text-xs font-semibold">{{ data.entity.name }}</div>
                                <div class="text-xs opacity-75 mt-1">{{ formatEntityType(data.entity.type) }}</div>
                            </div>
                        </template>
                    </VueFlow>
                </div>

                <!-- Entity Details Sidebar -->
                <div v-if="selectedEntity" class="fixed right-0 top-0 h-full w-96 bg-white border-l border-neutral-200 shadow-xl z-50 overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-neutral-900">Entity Details</h3>
                            <Button variant="ghost" size="sm" @click="selectedEntity = null">×</Button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <div class="text-sm font-medium text-neutral-500">Name</div>
                                <div class="text-base text-neutral-900 mt-1">{{ selectedEntity.name }}</div>
                            </div>
                            <div v-if="selectedEntity.description">
                                <div class="text-sm font-medium text-neutral-500">Description</div>
                                <div class="text-sm text-neutral-700 mt-1">{{ selectedEntity.description }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-neutral-500">Type</div>
                                <div class="text-sm text-neutral-700 mt-1">{{ formatEntityType(selectedEntity.type) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>

<style>
.vue-flow-container {
    width: 100%;
    height: 100%;
}
</style>


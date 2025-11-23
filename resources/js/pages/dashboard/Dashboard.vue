<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Button from '@/components/ui/Button.vue'
import graphService from '@/services/graph'

const router = useRouter()
const graphs = ref([])
const loading = ref(false)

const fetchGraphs = async () => {
    loading.value = true
    try {
        graphs.value = await graphService.index()
    } catch (err) {
        console.error('Failed to load graphs:', err)
        graphs.value = []
    } finally {
        loading.value = false
    }
}

const getStatusCounts = () => {
    const counts = {
        completed: 0,
        processing: 0,
        pending: 0,
        failed: 0,
    }
    graphs.value.forEach(graph => {
        if (counts.hasOwnProperty(graph.status)) {
            counts[graph.status]++
        }
    })
    return counts
}

onMounted(() => {
    fetchGraphs()
})
</script>

<template>
    <DefaultLayout>
        <div class="container mx-auto px-4 py-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900">Dashboard</h1>
                    <p class="text-sm text-neutral-500">Business Knowledge Graph Platform</p>
                </div>
                <Button @click="router.push({ name: 'graphs.create' })">
                    Create New Graph
                </Button>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-neutral-200 bg-white p-6">
                    <div class="text-3xl font-bold text-neutral-900">{{ graphs.length }}</div>
                    <div class="text-sm text-neutral-500">Total Graphs</div>
                </div>
                <div class="rounded-lg border border-neutral-200 bg-white p-6">
                    <div class="text-3xl font-bold text-green-600">{{ getStatusCounts().completed }}</div>
                    <div class="text-sm text-neutral-500">Completed</div>
                </div>
                <div class="rounded-lg border border-neutral-200 bg-white p-6">
                    <div class="text-3xl font-bold text-blue-600">{{ getStatusCounts().processing }}</div>
                    <div class="text-sm text-neutral-500">Processing</div>
                </div>
                <div class="rounded-lg border border-neutral-200 bg-white p-6">
                    <div class="text-3xl font-bold text-yellow-600">{{ getStatusCounts().pending }}</div>
                    <div class="text-sm text-neutral-500">Pending</div>
                </div>
            </div>

            <div class="mt-6 rounded-lg border border-neutral-200 bg-white p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-neutral-900">Recent Graphs</h2>
                    <Button variant="outline" size="sm" @click="router.push({ name: 'graphs.index' })">
                        View All
                    </Button>
                </div>
                <div v-if="loading" class="py-4 text-center text-neutral-500">Loading...</div>
                <div v-else-if="graphs.length === 0" class="py-8 text-center text-neutral-500">
                    <p class="mb-4">No graphs yet. Create your first knowledge graph to get started.</p>
                    <Button @click="router.push({ name: 'graphs.create' })">
                        Create Graph
                    </Button>
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="graph in graphs.slice(0, 5)"
                        :key="graph.id"
                        class="flex items-center justify-between rounded-lg border border-neutral-200 p-4 hover:bg-neutral-50 cursor-pointer"
                        @click="router.push({ name: 'graphs.show', params: { id: graph.id } })"
                    >
                        <div>
                            <div class="font-medium text-neutral-900">{{ graph.name }}</div>
                            <div class="text-sm text-neutral-500">{{ graph.website_url }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                    graph.status === 'completed' ? 'bg-green-100 text-green-800' :
                                    graph.status === 'processing' ? 'bg-blue-100 text-blue-800' :
                                    graph.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    'bg-red-100 text-red-800'
                                ]"
                            >
                                {{ graph.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>

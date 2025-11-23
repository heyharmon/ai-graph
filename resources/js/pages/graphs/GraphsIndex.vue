<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Button from '@/components/ui/Button.vue'
import graphService from '@/services/graph'

const router = useRouter()
const graphs = ref([])
const loading = ref(false)
const error = ref(null)

const fetchGraphs = async () => {
    loading.value = true
    error.value = null
    try {
        graphs.value = await graphService.index()
    } catch (err) {
        error.value = err?.message || 'Failed to load graphs.'
        graphs.value = []
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchGraphs()
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800'
    }
    return colors[status] || 'bg-neutral-100 text-neutral-800'
}
</script>

<template>
    <DefaultLayout>
        <div class="container mx-auto px-4 py-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900">Knowledge Graphs</h1>
                    <p class="text-sm text-neutral-500">Manage your business knowledge graphs</p>
                </div>
                <Button @click="router.push({ name: 'graphs.create' })">
                    Create Graph
                </Button>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white shadow-sm">
                <div class="border-b border-neutral-200 px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-neutral-900">All Graphs</h2>
                        <div v-if="loading" class="text-xs font-semibold uppercase tracking-wide text-neutral-400">Loading…</div>
                    </div>
                    <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
                </div>

                <div class="hidden md:block">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50 text-xs font-semibold uppercase tracking-wide text-neutral-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Website URL</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Entities</th>
                                <th class="px-4 py-3 text-left">Relationships</th>
                                <th class="px-4 py-3 text-left">Created</th>
                                <th class="px-4 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            <tr
                                v-for="graph in graphs"
                                :key="graph.id"
                                class="hover:bg-neutral-50/60 cursor-pointer"
                                @click="router.push({ name: 'graphs.show', params: { id: graph.id } })"
                            >
                                <td class="px-4 py-3 text-sm font-medium text-neutral-900">{{ graph.website_url }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                            getStatusColor(graph.status)
                                        ]"
                                    >
                                        {{ graph.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-neutral-600">{{ graph.entities_count || 0 }}</td>
                                <td class="px-4 py-3 text-sm text-neutral-600">{{ graph.relationships_count || 0 }}</td>
                                <td class="px-4 py-3 text-sm text-neutral-600">{{ formatDate(graph.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <Button size="sm" variant="outline" @click.stop="router.push({ name: 'graphs.show', params: { id: graph.id } })">
                                        View
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="!graphs.length && !loading">
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-neutral-500">No graphs found. Create your first graph to get started.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-4 px-4 py-4 md:hidden">
                    <div
                        v-for="graph in graphs"
                        :key="`card-${graph.id}`"
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/80 p-4 shadow-sm shadow-neutral-200/40 cursor-pointer"
                        @click="router.push({ name: 'graphs.show', params: { id: graph.id } })"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="text-base font-semibold text-neutral-900 truncate">{{ graph.website_url }}</div>
                                <div class="mt-1 text-sm text-neutral-600">
                                    {{ graph.entities_count || 0 }} entities · {{ graph.relationships_count || 0 }} relationships
                                </div>
                            </div>
                            <span
                                :class="[
                                    'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                    getStatusColor(graph.status)
                                ]"
                            >
                                {{ graph.status }}
                            </span>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <div class="text-xs text-neutral-500">Created {{ formatDate(graph.created_at) }}</div>
                            <Button size="sm" variant="outline" @click.stop="router.push({ name: 'graphs.show', params: { id: graph.id } })"> View </Button>
                        </div>
                    </div>
                    <div
                        v-if="!graphs.length && !loading"
                        class="rounded-xl border border-dashed border-neutral-300 bg-white/60 p-6 text-center text-sm text-neutral-500"
                    >
                        No graphs found. Create your first graph to get started.
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>


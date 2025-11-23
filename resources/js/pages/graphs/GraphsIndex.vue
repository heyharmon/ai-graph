<script setup>
import { ref, onMounted } from 'vue'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import graph from '@/services/graph'

const graphs = ref([])
const loading = ref(false)
const error = ref(null)

const fetchGraphs = async () => {
    loading.value = true
    error.value = null
    try {
        graphs.value = await graph.getAll()
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
</script>

<template>
    <DefaultLayout>
        <div class="container mx-auto px-4 py-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-neutral-900">Graphs</h1>
                <router-link
                    :to="{ name: 'graphs.create' }"
                    class="inline-flex items-center rounded-md border border-neutral-900 bg-neutral-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-neutral-800"
                >
                    Create New
                </router-link>
            </div>

            <div v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-600">
                {{ error }}
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white shadow-sm">
                <div v-if="loading" class="border-b border-neutral-200 px-4 py-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-neutral-400">Loading…</div>
                </div>

                <div class="hidden md:block">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50 text-xs font-semibold uppercase tracking-wide text-neutral-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Website URL</th>
                                <th class="px-4 py-3 text-left">Sources</th>
                                <th class="px-4 py-3 text-left">Created</th>
                                <th class="px-4 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            <tr
                                v-for="g in graphs"
                                :key="g.id"
                                class="hover:bg-neutral-50/60 cursor-pointer"
                                @click="$router.push({ name: 'sources.index', params: { id: g.id } })"
                            >
                                <td class="px-4 py-3 text-sm font-medium text-neutral-900">{{ g.website_url }}</td>
                                <td class="px-4 py-3 text-sm text-neutral-600">{{ g.sources_count || 0 }}</td>
                                <td class="px-4 py-3 text-sm text-neutral-600">{{ formatDate(g.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <button
                                        @click.stop="$router.push({ name: 'sources.index', params: { id: g.id } })"
                                        class="rounded-md border border-neutral-300 px-3 py-1 text-sm text-neutral-700 hover:bg-neutral-50 focus:outline-none"
                                    >
                                        View Sources
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!graphs.length && !loading">
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-neutral-500">
                                    No graphs found. 
                                    <router-link :to="{ name: 'graphs.create' }" class="text-blue-600 hover:underline">Create your first one</router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-4 px-4 py-4 md:hidden">
                    <div
                        v-for="g in graphs"
                        :key="`card-${g.id}`"
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/80 p-4 shadow-sm shadow-neutral-200/40 cursor-pointer"
                        @click="$router.push({ name: 'sources.index', params: { id: g.id } })"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="text-base font-semibold text-neutral-900 break-words">{{ g.website_url }}</div>
                                <div class="mt-1 text-sm text-neutral-600">{{ g.sources_count || 0 }} sources</div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <div class="text-xs text-neutral-500">Created {{ formatDate(g.created_at) }}</div>
                            <button
                                @click.stop="$router.push({ name: 'sources.index', params: { id: g.id } })"
                                class="rounded-md border border-neutral-300 px-3 py-1 text-sm text-neutral-700 hover:bg-neutral-50 focus:outline-none"
                            >
                                View Sources
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="!graphs.length && !loading"
                        class="rounded-xl border border-dashed border-neutral-300 bg-white/60 p-6 text-center text-sm text-neutral-500"
                    >
                        No graphs found.
                        <router-link :to="{ name: 'graphs.create' }" class="block mt-2 text-blue-600 hover:underline">Create your first one</router-link>
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>


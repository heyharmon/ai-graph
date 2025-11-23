<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import graph from '@/services/graph'

const route = useRoute()
const router = useRouter()
const graphId = ref(route.params.id)

const sources = ref([])
const loading = ref(false)
const error = ref('')
const searchQuery = ref('')
const sortBy = ref('discovered_at')
const sortOrder = ref('desc')
const currentPage = ref(1)
const perPage = ref(25)
const pagination = ref({
    current_page: 1,
    per_page: 25,
    total: 0,
    last_page: 1,
    from: 0,
    to: 0,
})

const fetchSources = async () => {
    loading.value = true
    error.value = ''

    try {
        const params = {
            page: currentPage.value,
            per_page: perPage.value,
            sort_by: sortBy.value,
            sort_order: sortOrder.value,
        }

        if (searchQuery.value.trim()) {
            params.search = searchQuery.value.trim()
        }

        const response = await graph.getSources(graphId.value, params)
        
        sources.value = response.sources || []
        pagination.value = response.pagination || pagination.value
    } catch (err) {
        error.value = err.message || 'Failed to load sources. Please try again.'
    } finally {
        loading.value = false
    }
}

const handleSearch = () => {
    currentPage.value = 1
    fetchSources()
}

const handleSort = (column) => {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortBy.value = column
        sortOrder.value = 'asc'
    }
    currentPage.value = 1
    fetchSources()
}

const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        currentPage.value = page
        fetchSources()
    }
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
    fetchSources()
})

// Watch for route param changes
watch(() => route.params.id, (newId) => {
    if (newId) {
        graphId.value = newId
        currentPage.value = 1
        fetchSources()
    }
})
</script>

<template>
    <DefaultLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-neutral-900">Sources</h1>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-600">
                {{ error }}
            </div>

            <!-- Search and Controls -->
            <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex-1">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by URL or title..."
                        @keyup.enter="handleSearch"
                        class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm text-neutral-900 focus:border-neutral-500 focus:outline-none sm:max-w-md"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="handleSearch"
                        class="rounded-md bg-neutral-900 px-3 py-2 text-sm text-white hover:bg-neutral-800 focus:outline-none"
                    >
                        Search
                    </button>
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''; handleSearch()"
                        class="rounded-md border border-neutral-300 px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50 focus:outline-none"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white shadow-sm">
                <div v-if="loading" class="border-b border-neutral-200 px-4 py-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-neutral-400">Loading…</div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="px-4 py-12 text-center">
                    <div class="text-neutral-500">Loading sources...</div>
                </div>

                <!-- Empty State -->
                <div v-else-if="!loading && sources.length === 0" class="px-4 py-12 text-center">
                    <p class="text-neutral-600">
                        <span v-if="searchQuery">No sources found matching your search.</span>
                        <span v-else>No sources found.</span>
                    </p>
                </div>

                <!-- Sources Table -->
                <div v-else>
                    <div class="hidden md:block">
                        <table class="min-w-full divide-y divide-neutral-200">
                            <thead class="bg-neutral-50 text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">#</th>
                                    <th
                                        @click="handleSort('title')"
                                        class="cursor-pointer px-4 py-3 text-left hover:bg-neutral-100"
                                    >
                                        Title
                                        <span v-if="sortBy === 'title'" class="ml-1">
                                            {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    </th>
                                    <th
                                        @click="handleSort('url')"
                                        class="cursor-pointer px-4 py-3 text-left hover:bg-neutral-100"
                                    >
                                        URL
                                        <span v-if="sortBy === 'url'" class="ml-1">
                                            {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    </th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th
                                        @click="handleSort('discovered_at')"
                                        class="cursor-pointer px-4 py-3 text-left hover:bg-neutral-100"
                                    >
                                        Discovered At
                                        <span v-if="sortBy === 'discovered_at'" class="ml-1">
                                            {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200">
                                <tr
                                    v-for="(source, index) in sources"
                                    :key="source.id"
                                    class="hover:bg-neutral-50/60"
                                >
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500">
                                        {{ pagination.from + index }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-neutral-900">
                                        {{ source.title || '(No title)' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <a
                                            :href="source.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-blue-600 hover:text-blue-800 hover:underline"
                                        >
                                            {{ source.url }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-800': source.status === 'discovered',
                                                'bg-red-100 text-red-800': source.status === 'failed',
                                            }"
                                            class="rounded-full px-2 py-1 text-xs font-medium"
                                        >
                                            {{ source.status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500">
                                        {{ formatDate(source.discovered_at) }}
                                    </td>
                                </tr>
                                <tr v-if="!sources.length && !loading">
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-neutral-500">
                                        <span v-if="searchQuery">No sources found matching your search.</span>
                                        <span v-else>No sources found.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile View -->
                    <div class="space-y-4 px-4 py-4 md:hidden">
                        <div
                            v-for="source in sources"
                            :key="`card-${source.id}`"
                            class="rounded-2xl border border-neutral-200 bg-neutral-50/80 p-4 shadow-sm shadow-neutral-200/40"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="text-base font-semibold text-neutral-900 break-words">
                                        {{ source.title || '(No title)' }}
                                    </div>
                                    <a
                                        :href="source.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-1 block text-sm text-blue-600 hover:text-blue-800 hover:underline break-all"
                                    >
                                        {{ source.url }}
                                    </a>
                                </div>
                                <span
                                    :class="{
                                        'bg-green-100 text-green-800': source.status === 'discovered',
                                        'bg-red-100 text-red-800': source.status === 'failed',
                                    }"
                                    class="rounded-full px-2 py-1 text-xs font-medium"
                                >
                                    {{ source.status }}
                                </span>
                            </div>
                            <div class="mt-3 text-xs text-neutral-500">
                                Discovered {{ formatDate(source.discovered_at) }}
                            </div>
                        </div>
                        <div
                            v-if="!sources.length && !loading"
                            class="rounded-xl border border-dashed border-neutral-300 bg-white/60 p-6 text-center text-sm text-neutral-500"
                        >
                            <span v-if="searchQuery">No sources found matching your search.</span>
                            <span v-else>No sources found.</span>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.last_page > 1" class="border-t border-neutral-200 bg-neutral-50 px-4 py-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-neutral-700">
                                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} sources
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="goToPage(currentPage - 1)"
                                    :disabled="currentPage === 1"
                                    class="rounded-md border border-neutral-300 px-3 py-1 text-sm text-neutral-700 hover:bg-neutral-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Previous
                                </button>
                                <template v-for="page in pagination.last_page" :key="page">
                                    <button
                                        v-if="page === 1 || page === pagination.last_page || (page >= currentPage - 2 && page <= currentPage + 2)"
                                        @click="goToPage(page)"
                                        :class="{
                                            'bg-neutral-900 text-white': page === currentPage,
                                            'border border-neutral-300 text-neutral-700 hover:bg-neutral-50': page !== currentPage,
                                        }"
                                        class="rounded-md px-3 py-1 text-sm"
                                    >
                                        {{ page }}
                                    </button>
                                    <span
                                        v-else-if="page === currentPage - 3 || page === currentPage + 3"
                                        class="px-2 py-1 text-sm text-neutral-500"
                                    >
                                        ...
                                    </span>
                                </template>
                                <button
                                    @click="goToPage(currentPage + 1)"
                                    :disabled="currentPage === pagination.last_page"
                                    class="rounded-md border border-neutral-300 px-3 py-1 text-sm text-neutral-700 hover:bg-neutral-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>


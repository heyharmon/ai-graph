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
        <div class="min-h-screen bg-neutral-50 py-8 px-4">
        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-neutral-900">Sources</h1>
                <p class="mt-1 text-neutral-600">
                    View and manage all discovered sources for this graph
                </p>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-500">
                {{ error }}
            </div>

            <!-- Search and Controls -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex-1">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by URL or title..."
                        @keyup.enter="handleSearch"
                        class="w-full rounded-md border border-neutral-300 px-4 py-2 text-neutral-900 focus:border-neutral-500 focus:outline-none sm:max-w-md"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="handleSearch"
                        class="rounded-md bg-neutral-900 px-4 py-2 text-white hover:bg-neutral-800 focus:outline-none"
                    >
                        Search
                    </button>
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''; handleSearch()"
                        class="rounded-md border border-neutral-300 px-4 py-2 text-neutral-700 hover:bg-neutral-50 focus:outline-none"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <div class="text-neutral-500">Loading sources...</div>
            </div>

            <!-- Empty State -->
            <div v-else-if="!loading && sources.length === 0" class="rounded-lg border border-neutral-200 bg-white p-12 text-center">
                <p class="text-neutral-600">
                    <span v-if="searchQuery">No sources found matching your search.</span>
                    <span v-else>No sources found.</span>
                </p>
            </div>

            <!-- Sources Table -->
            <div v-else class="overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                    #
                                </th>
                                <th
                                    @click="handleSort('title')"
                                    class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:bg-neutral-100"
                                >
                                    Title
                                    <span v-if="sortBy === 'title'" class="ml-1">
                                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                <th
                                    @click="handleSort('url')"
                                    class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:bg-neutral-100"
                                >
                                    URL
                                    <span v-if="sortBy === 'url'" class="ml-1">
                                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                    Status
                                </th>
                                <th
                                    @click="handleSort('discovered_at')"
                                    class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:bg-neutral-100"
                                >
                                    Discovered At
                                    <span v-if="sortBy === 'discovered_at'" class="ml-1">
                                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white">
                            <tr
                                v-for="(source, index) in sources"
                                :key="source.id"
                                class="hover:bg-neutral-50"
                            >
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-neutral-500">
                                    {{ pagination.from + index }}
                                </td>
                                <td class="px-6 py-4 text-sm text-neutral-900">
                                    {{ source.title || '(No title)' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a
                                        :href="source.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-blue-600 hover:text-blue-800 hover:underline"
                                    >
                                        {{ source.url }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
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
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-neutral-500">
                                    {{ formatDate(source.discovered_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="border-t border-neutral-200 bg-neutral-50 px-6 py-4">
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


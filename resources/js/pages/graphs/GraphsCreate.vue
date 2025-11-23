<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import graph from '@/services/graph'

const router = useRouter()
const websiteUrl = ref('')
const loading = ref(false)
const error = ref('')
const graphData = ref(null)
const totalSources = ref(0)

const submitWebsite = async () => {
    if (!websiteUrl.value.trim()) {
        error.value = 'Please enter a website URL'
        return
    }

    loading.value = true
    error.value = ''
    graphData.value = null
    totalSources.value = 0

    try {
        const response = await graph.create(websiteUrl.value.trim())
        
        graphData.value = response.graph
        totalSources.value = response.graph.sources_count || 0
        
        // Auto-redirect to sources page after 2 seconds
        setTimeout(() => {
            router.push(`/graphs/${graphData.value.id}/sources`)
        }, 2000)
    } catch (err) {
        error.value = err.message || err.website_url?.[0] || 'Failed to crawl website. Please try again.'
    } finally {
        loading.value = false
    }
}

const viewSources = () => {
    if (graphData.value) {
        router.push(`/graphs/${graphData.value.id}/sources`)
    }
}
</script>

<template>
    <DefaultLayout>
        <div class="container mx-auto px-4 py-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-neutral-900">Create Graph</h1>
            </div>

            <!-- Input Form -->
            <div v-if="!graphData" class="max-w-2xl space-y-4">
                <div v-if="error" class="rounded-md bg-red-50 p-4 text-sm text-red-500">
                    {{ error }}
                </div>

                <div>
                    <label for="website_url" class="mb-1 block text-sm font-medium text-neutral-700">
                        Website URL
                    </label>
                    <input
                        id="website_url"
                        v-model="websiteUrl"
                        type="url"
                        placeholder="https://example.com"
                        required
                        :disabled="loading"
                        class="w-full rounded-md border border-neutral-300 px-3 py-2 text-neutral-900 focus:border-neutral-500 focus:outline-none disabled:opacity-70"
                    />
                    <p class="mt-1 text-xs text-neutral-500">
                        We'll crawl your sitemap.xml to discover all pages (up to 1,000 pages)
                    </p>
                </div>

                <div>
                    <button
                        type="button"
                        @click="submitWebsite"
                        :disabled="loading || !websiteUrl.trim()"
                        class="rounded-md bg-neutral-900 px-4 py-2 text-white hover:bg-neutral-800 focus:outline-none disabled:opacity-70"
                    >
                        <span v-if="loading">Crawling website...</span>
                        <span v-else>Start Crawling</span>
                    </button>
                </div>
            </div>

            <!-- Success Message -->
            <div v-else class="max-w-2xl space-y-6">
                <div class="rounded-md bg-green-50 p-6">
                    <h2 class="mb-2 text-lg font-semibold text-green-900">Crawl Complete!</h2>
                    <p class="mb-4 text-green-700">
                        Successfully discovered <strong>{{ totalSources }}</strong> page{{ totalSources !== 1 ? 's' : '' }}
                    </p>
                    <p class="text-sm text-green-600">
                        Redirecting to sources page...
                    </p>
                </div>

                <div class="flex justify-end space-x-4">
                    <button
                        type="button"
                        @click="graphData = null; totalSources = 0"
                        class="rounded-md border border-neutral-300 px-4 py-2 text-neutral-700 hover:bg-neutral-50 focus:outline-none"
                    >
                        Start Over
                    </button>
                    <button
                        type="button"
                        @click="viewSources"
                        class="rounded-md bg-neutral-900 px-4 py-2 text-white hover:bg-neutral-800 focus:outline-none"
                    >
                        View Sources
                    </button>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>


<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import knowledgeGraph from '@/services/knowledgeGraph'

const router = useRouter()
const websiteUrl = ref('')
const loading = ref(false)
const error = ref('')
const knowledgeGraphData = ref(null)
const sourcesPreview = ref([])
const totalSources = ref(0)

const submitWebsite = async () => {
    if (!websiteUrl.value.trim()) {
        error.value = 'Please enter a website URL'
        return
    }

    loading.value = true
    error.value = ''
    knowledgeGraphData.value = null
    sourcesPreview.value = []
    totalSources.value = 0

    try {
        const response = await knowledgeGraph.create(websiteUrl.value.trim())
        
        knowledgeGraphData.value = response.knowledge_graph
        sourcesPreview.value = response.sources_preview || []
        totalSources.value = response.knowledge_graph.sources_count || 0
    } catch (err) {
        error.value = err.message || err.website_url?.[0] || 'Failed to crawl website. Please try again.'
    } finally {
        loading.value = false
    }
}

const continueToNextStep = () => {
    // Phase 1 stops here - Phase 2 will add entity extraction
    // For now, redirect to dashboard
    router.push('/admin')
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-neutral-50 py-12 px-4">
        <div class="w-full max-w-3xl">
            <div class="mb-8 text-center">
                <h1 class="mb-2 text-3xl font-bold text-neutral-900">Create Your Knowledge Graph</h1>
                <p class="text-neutral-600">Enter your website URL to discover all pages</p>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white p-8 shadow-sm">
                <!-- Input Form -->
                <div v-if="!knowledgeGraphData" class="space-y-4">
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
                            class="w-full rounded-md bg-neutral-900 px-4 py-2 text-white hover:bg-neutral-800 focus:outline-none disabled:opacity-70"
                        >
                            <span v-if="loading">Crawling website...</span>
                            <span v-else>Start Crawling</span>
                        </button>
                    </div>
                </div>

                <!-- Results -->
                <div v-else class="space-y-6">
                    <div class="rounded-md bg-green-50 p-4">
                        <h2 class="mb-2 text-lg font-semibold text-green-900">Crawl Complete!</h2>
                        <p class="text-green-700">
                            Successfully discovered <strong>{{ totalSources }}</strong> page{{ totalSources !== 1 ? 's' : '' }}
                        </p>
                    </div>

                    <div>
                        <h3 class="mb-3 text-sm font-medium text-neutral-700">Preview of Discovered Pages</h3>
                        <div class="max-h-96 overflow-y-auto rounded-md border border-neutral-200">
                            <table class="min-w-full divide-y divide-neutral-200">
                                <thead class="bg-neutral-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                            #
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                            Title
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                            URL
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-neutral-200 bg-white">
                                    <tr v-for="(source, index) in sourcesPreview" :key="source.id" class="hover:bg-neutral-50">
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500">
                                            {{ index + 1 }}
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
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-if="totalSources > 10" class="mt-2 text-xs text-neutral-500">
                            Showing first 10 of {{ totalSources }} pages
                        </p>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <button
                            type="button"
                            @click="knowledgeGraphData = null; sourcesPreview = []; totalSources = 0"
                            class="rounded-md border border-neutral-300 px-4 py-2 text-neutral-700 hover:bg-neutral-50 focus:outline-none"
                        >
                            Start Over
                        </button>
                        <button
                            type="button"
                            @click="continueToNextStep"
                            class="rounded-md bg-neutral-900 px-4 py-2 text-white hover:bg-neutral-800 focus:outline-none"
                        >
                            Continue
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


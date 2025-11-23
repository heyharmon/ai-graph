<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import graphService from '@/services/graph'

const router = useRouter()
const websiteUrl = ref('')
const name = ref('')
const loading = ref(false)
const error = ref(null)

const handleSubmit = async () => {
    if (!websiteUrl.value) {
        error.value = 'Website URL is required'
        return
    }

    loading.value = true
    error.value = null

    try {
        const graph = await graphService.create({
            website_url: websiteUrl.value,
            name: name.value || undefined,
        })
        router.push({ name: 'graphs.show', params: { id: graph.id } })
    } catch (err) {
        error.value = err?.message || 'Failed to create graph. Please check your API keys (EXA_API_KEY and OPENAI_API_KEY) are configured.'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <DefaultLayout>
        <div class="container mx-auto px-4 py-8 max-w-2xl">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-neutral-900">Create Knowledge Graph</h1>
                <p class="text-sm text-neutral-500 mt-1">Enter a website URL to analyze and build a knowledge graph</p>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white shadow-sm p-6">
                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div>
                        <label for="website_url" class="block text-sm font-medium text-neutral-700 mb-2">
                            Website URL *
                        </label>
                        <Input
                            id="website_url"
                            v-model="websiteUrl"
                            type="url"
                            placeholder="https://example.com"
                            required
                            :disabled="loading"
                        />
                        <p class="mt-1 text-xs text-neutral-500">The system will crawl this website and extract business entities</p>
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-neutral-700 mb-2">
                            Graph Name (Optional)
                        </label>
                        <Input
                            id="name"
                            v-model="name"
                            type="text"
                            placeholder="My Business Graph"
                            :disabled="loading"
                        />
                        <p class="mt-1 text-xs text-neutral-500">Leave blank to use the website domain as the name</p>
                    </div>

                    <div v-if="error" class="rounded-md bg-red-50 p-3">
                        <p class="text-sm text-red-800">{{ error }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-4">
                        <Button type="submit" :disabled="loading">
                            {{ loading ? 'Creating...' : 'Create Graph' }}
                        </Button>
                        <Button 
                            type="button" 
                            variant="outline" 
                            @click="router.back()"
                            :disabled="loading"
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                <h3 class="text-sm font-semibold text-neutral-900 mb-2">What happens next?</h3>
                <ul class="text-sm text-neutral-600 space-y-1 list-disc list-inside">
                    <li>The system will crawl your website using Exa</li>
                    <li>AI will analyze the content and extract entities (products, services, locations, customer types, etc.)</li>
                    <li>Relationships between entities will be mapped</li>
                    <li>An interactive knowledge graph will be generated</li>
                </ul>
                <p class="text-xs text-neutral-500 mt-3">This process typically takes 5-10 minutes for a typical website.</p>
            </div>
        </div>
    </DefaultLayout>
</template>


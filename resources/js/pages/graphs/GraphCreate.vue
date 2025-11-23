<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import graphService from '@/services/graph'

const router = useRouter()
const websiteUrl = ref('')
const loading = ref(false)
const error = ref(null)

const handleSubmit = async () => {
    if (!websiteUrl.value.trim()) {
        error.value = 'Please enter a website URL'
        return
    }

    loading.value = true
    error.value = null

    try {
        const graph = await graphService.create({
            website_url: websiteUrl.value.trim()
        })
        router.push({ name: 'graphs.show', params: { id: graph.id } })
    } catch (err) {
        error.value = err?.message || err?.errors?.website_url?.[0] || 'Failed to create graph'
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
                <p class="text-sm text-neutral-500 mt-1">Enter a website URL to analyze and extract business entities</p>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white shadow-sm p-6">
                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div>
                        <label for="website_url" class="block text-sm font-medium text-neutral-900 mb-2">
                            Website URL
                        </label>
                        <Input
                            id="website_url"
                            v-model="websiteUrl"
                            type="url"
                            placeholder="https://example.com"
                            required
                            :disabled="loading"
                        />
                        <p class="mt-1 text-xs text-neutral-500">
                            Enter the full URL of the website you want to analyze
                        </p>
                    </div>

                    <div v-if="error" class="rounded-md bg-red-50 p-3">
                        <p class="text-sm text-red-800">{{ error }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-4">
                        <Button type="submit" :disabled="loading">
                            {{ loading ? 'Creating...' : 'Create Graph' }}
                        </Button>
                        <Button variant="outline" type="button" @click="router.back()" :disabled="loading">
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                <h3 class="text-sm font-semibold text-neutral-900 mb-2">What happens next?</h3>
                <ul class="text-sm text-neutral-600 space-y-1 list-disc list-inside">
                    <li>The system will analyze the website content using AI</li>
                    <li>Entities like products, services, locations, and customer types will be extracted</li>
                    <li>Relationships between entities will be mapped</li>
                    <li>An interactive knowledge graph will be generated</li>
                </ul>
                <p class="text-xs text-neutral-500 mt-3">
                    Processing typically takes 5-10 minutes depending on website size
                </p>
            </div>
        </div>
    </DefaultLayout>
</template>


<script setup>
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Button from '@/components/ui/Button.vue'

const router = useRouter()
</script>

<template>
    <DefaultLayout>
        <div class="container mx-auto px-4 py-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-neutral-900">Dashboard</h1>
                <p class="text-sm text-neutral-500 mt-1">Welcome to the Business Knowledge Graph Platform</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-2">Knowledge Graphs</h2>
                    <p class="text-sm text-neutral-600 mb-4">
                        Create and manage knowledge graphs by analyzing website content to extract business entities and relationships.
                    </p>
                    <Button @click="router.push({ name: 'graphs.index' })">
                        View Graphs
                    </Button>
                </div>

                <div class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-2">Create New Graph</h2>
                    <p class="text-sm text-neutral-600 mb-4">
                        Start analyzing a website to build a knowledge graph of its business structure.
                    </p>
                    <Button @click="router.push({ name: 'graphs.create' })">
                        Create Graph
                    </Button>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>

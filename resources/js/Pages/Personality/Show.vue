<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    topGenres: { type: Array, default: () => [] },
    topStyles: { type: Array, default: () => [] },
    collectionSize: { type: Number, default: 0 },
    insight: { type: String, default: '' },
    hasToken: { type: Boolean, default: false },
    username: { type: String, default: '' },
});
</script>

<template>
    <AppLayout>
        <Head title="Your Music Personality" />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <Link
                :href="route('home')"
                class="inline-flex items-center gap-2 text-gray-500 hover:text-white text-sm mb-8 transition-colors"
            >
                ← Back
            </Link>

            <div class="text-center mb-10">
                <span class="text-4xl">🎭</span>
                <h1 class="text-3xl font-bold text-white mt-3">Your Music Personality</h1>
                <p class="text-gray-500 mt-2">
                    Based on your collection of {{ collectionSize }} release{{ collectionSize !== 1 ? 's' : '' }}
                </p>
            </div>

            <div v-if="!username" class="text-center py-16">
                <div class="text-6xl mb-4">⚫</div>
                <h2 class="text-xl font-semibold text-gray-300 mb-2">No collection synced yet</h2>
                <p class="text-gray-500">
                    Set
                    <code class="text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded text-sm">DISCOGS_USERNAME</code> in
                    .env and run
                    <code class="text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded text-sm">sail artisan discogs:sync</code>
                    to get started.
                </p>
            </div>

            <div v-else-if="collectionSize === 0" class="text-center py-16">
                <div class="text-6xl mb-4">⚫</div>
                <h2 class="text-xl font-semibold text-gray-300 mb-2">Your collection is empty</h2>
                <p class="text-gray-500">Sync your Discogs collection to see your music personality.</p>
            </div>

            <template v-else>
                <!-- AI Personality Insight -->
                <div class="mb-10">
                    <h2 class="text-lg font-semibold text-white mb-4">Personality Insight</h2>

                    <div v-if="!hasToken" class="rounded-xl bg-gray-900 border border-gray-800 p-6 text-center">
                        <p class="text-gray-400">
                            Add a
                            <code class="text-gray-300 bg-gray-800 px-1.5 py-0.5 rounded text-sm">HUGGINGFACE_API_TOKEN</code>
                            to .env to unlock AI-powered personality insights.
                        </p>
                    </div>

                    <div v-else-if="!insight" class="rounded-xl bg-gray-900 border border-gray-800 p-6 text-center">
                        <p class="text-gray-500">Could not generate a personality insight — the AI model may be warming up. Try again shortly.</p>
                    </div>

                    <div v-else class="rounded-xl bg-gray-900 border border-gray-800 p-6">
                        <p class="text-gray-200 leading-relaxed whitespace-pre-line">{{ insight }}</p>
                    </div>

                    <p class="text-gray-600 text-xs mt-3 text-center">Uses AI — results are inferred from your top styles and genres.</p>
                </div>

                <!-- Top Genres & Styles -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div v-if="topGenres.length">
                        <h2 class="text-lg font-semibold text-white mb-4">Top Genres</h2>
                        <div class="space-y-2">
                            <div
                                v-for="g in topGenres"
                                :key="g.name"
                                class="flex items-center justify-between rounded-lg bg-gray-900 border border-gray-800 px-4 py-2.5"
                            >
                                <span class="text-white text-sm">{{ g.name }}</span>
                                <span class="text-gray-500 text-xs">{{ g.count }} release{{ g.count !== 1 ? 's' : '' }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="topStyles.length">
                        <h2 class="text-lg font-semibold text-white mb-4">Top Styles</h2>
                        <div class="space-y-2">
                            <div
                                v-for="s in topStyles"
                                :key="s.name"
                                class="flex items-center justify-between rounded-lg bg-gray-900 border border-gray-800 px-4 py-2.5"
                            >
                                <span class="text-white text-sm">{{ s.name }}</span>
                                <span class="text-gray-500 text-xs">{{ s.count }} release{{ s.count !== 1 ? 's' : '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>

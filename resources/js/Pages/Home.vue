<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    moods: { type: Array, default: () => [] },
    username: { type: String, default: '' },
});
</script>

<template>
    <AppLayout>
        <Head title="Discover" />

        <div class="min-h-[calc(100vh-4rem)] flex flex-col">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-1 flex flex-col justify-center">
                <h1 class="text-4xl sm:text-5xl font-bold text-white text-center mb-3">
                    What’s the vibe?
                </h1>
                <p class="text-gray-500 text-center mb-12">
                    Pick a mood and we’ll suggest something from your collection
                </p>

                <div v-if="!username" class="text-center py-16">
                    <div class="text-6xl mb-4">⚫</div>
                    <h2 class="text-xl font-semibold text-gray-300 mb-2">No collection synced yet</h2>
                    <p class="text-gray-500 mb-6">
                        Set <code class="text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded text-sm">DISCOGS_USERNAME</code> in .env and run <code class="text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded text-sm">sail artisan discogs:sync</code> to get started.
                    </p>
                </div>

                <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
                    <Link
                        v-for="mood in moods"
                        :key="mood.slug"
                        :href="route('mood.suggest', mood.slug)"
                        class="group flex flex-col items-center justify-center aspect-square sm:aspect-[4/3] bg-gray-900 border-2 border-gray-800 rounded-2xl p-6 text-center transition-all hover:border-gray-600 hover:bg-gray-800/80"
                    >
                        <span class="text-4xl sm:text-5xl mb-3 block group-hover:scale-110 transition-transform">{{ mood.emoji }}</span>
                        <span class="text-lg sm:text-xl font-semibold text-white">{{ mood.label }}</span>
                    </Link>
                </div>

                <div class="mt-16 text-center">
                    <Link
                        :href="route('collection.index')"
                        class="text-gray-500 hover:text-white text-sm transition-colors"
                    >
                        Browse full collection →
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

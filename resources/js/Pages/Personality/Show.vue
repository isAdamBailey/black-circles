<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    insight: { type: String, default: '' },
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
            </div>

            <div v-if="insight" class="rounded-xl bg-gray-900 border border-gray-800 p-6">
                <p class="text-gray-200 leading-relaxed whitespace-pre-line">{{ insight }}</p>
            </div>

            <div v-else class="rounded-xl bg-gray-900 border border-gray-800 p-6 text-center">
                <p class="text-gray-500">
                    No personality insight yet. Run <code class="text-gray-400">personality:generate</code> or wait for
                    the next weekly sync.
                </p>
            </div>

            <p class="text-gray-600 text-xs mt-3 text-center">
                Uses AI — results are inferred from your top styles and genres.
            </p>
        </div>
    </AppLayout>
</template>

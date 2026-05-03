<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    moods: { type: Array, default: () => [] },
    username: { type: String, default: '' },
    insight: { type: String, default: '' },
});

const prompt = ref('');
const processing = ref(false);
const moodSectionOpen = ref(false);

function submitVibe() {
    const p = prompt.value.trim();
    if (!p || p.length < 3) return;
    processing.value = true;
    router.post(
        route('vibe.suggest'),
        { prompt: p },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        }
    );
}
</script>

<template>
    <AppLayout>
        <Head title="Discover" />

        <div class="min-h-[calc(100vh-4rem)] flex flex-col">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-1 flex flex-col justify-center">
                <h1 class="text-4xl sm:text-5xl font-bold text-white text-center mb-3">Adam&apos;s Black Circles</h1>
                <p class="text-gray-400 text-center mb-8 max-w-3xl mx-auto">
                    This is an AI-driven app to search and pick records based on mood. Browse and sort the full
                    collection, or jump into random releases.
                </p>

                <div class="mb-8 flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-6">
                    <Link
                        v-if="username"
                        :href="route('collection.random')"
                        class="inline-flex items-center justify-center gap-2 min-w-[250px] px-6 py-3.5 bg-white text-gray-900 hover:bg-gray-200 rounded-lg text-base font-semibold transition-colors"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        Random release
                    </Link>
                    <Link
                        :href="route('collection.index')"
                        class="inline-flex items-center justify-center min-w-[250px] px-6 py-3.5 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-lg text-white text-base font-semibold transition-colors"
                    >
                        Browse collection
                    </Link>
                </div>

                <div class="mb-8">
                    <button
                        type="button"
                        class="mx-auto w-full max-w-3xl flex items-center justify-between rounded-xl border border-gray-700 bg-gray-900/80 px-5 py-4 text-left text-white hover:border-gray-500 hover:bg-gray-800 transition-colors"
                        @click="moodSectionOpen = !moodSectionOpen"
                    >
                        <span class="text-lg sm:text-xl font-semibold">Pick a mood</span>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-gray-300 transition-transform"
                            :class="{ 'rotate-180': moodSectionOpen }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <div v-if="moodSectionOpen">
                    <form
                        v-if="username"
                        class="mb-2 flex flex-col sm:flex-row gap-3 max-w-3xl mx-auto w-full"
                        @submit.prevent="submitVibe"
                    >
                        <input
                            v-model="prompt"
                            type="text"
                            placeholder="e.g. dark moody post-punk for a late night drive"
                            class="flex-1 px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-gray-500 focus:ring-1 focus:ring-gray-500"
                            :disabled="processing"
                        />
                        <PrimaryButton type="submit" :disabled="processing || !prompt.trim() || prompt.trim().length < 3">
                            {{ processing ? 'Finding...' : 'Find it' }}
                        </PrimaryButton>
                    </form>
                    <p v-if="username" class="text-center text-gray-600 text-sm mb-6">
                        Uses AI on Adam&apos;s collection — results may vary
                    </p>

                    <div v-if="!username" class="text-center py-12">
                        <div class="text-6xl mb-4">⚫</div>
                        <h2 class="text-xl font-semibold text-gray-300 mb-2">No collection synced yet</h2>
                        <p class="text-gray-500 mb-2">
                            Set
                            <code class="text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded text-sm">DISCOGS_USERNAME</code>
                            in .env and run
                            <code class="text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded text-sm"
                                >sail artisan discogs:sync</code
                            >
                            to get started.
                        </p>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6"
                        :class="{ 'pointer-events-none opacity-60': processing }"
                    >
                        <Link
                            v-for="mood in moods"
                            :key="mood.slug"
                            :href="route('mood.suggest', mood.slug)"
                            class="group flex flex-col items-center justify-center aspect-square sm:aspect-[4/3] bg-gray-900 border-2 border-gray-800 rounded-2xl p-6 text-center transition-all hover:border-gray-600 hover:bg-gray-800/80"
                        >
                            <span class="text-4xl sm:text-5xl mb-3 block group-hover:scale-110 transition-transform">{{
                                mood.emoji
                            }}</span>
                            <span class="text-lg sm:text-xl font-semibold text-white">{{ mood.label }}</span>
                        </Link>
                    </div>
                </div>

                <div v-if="username && insight" class="mt-8 rounded-xl bg-gray-900 border border-gray-800 p-6">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">
                            Adam&apos;s music personality
                        </h2>
                        <span class="text-[11px] text-gray-500 uppercase tracking-wider">AI generated</span>
                    </div>
                    <p class="text-gray-200 leading-relaxed whitespace-pre-line">{{ insight }}</p>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

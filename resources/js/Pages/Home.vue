<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    moods: { type: Array, default: () => [] },
    username: { type: String, default: '' },
    insight: { type: String, default: '' },
});

const prompt = ref('');
const processing = ref(false);
const moodSectionOpen = ref(true);
const vibeError = ref('');

function submitVibe() {
    const p = prompt.value.trim();
    if (!p || p.length < 3) return;
    processing.value = true;
    vibeError.value = '';
    router.post(
        route('vibe.suggest'),
        { prompt: p },
        {
            preserveScroll: true,
            onError: (errors) => {
                vibeError.value = errors.prompt || 'Something went wrong finding a match — try again.';
            },
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
                <h1 class="text-4xl sm:text-5xl font-bold tracking-[-0.02em] text-balance text-white text-center mb-3">
                    Adam&apos;s Black Circles
                </h1>
                <p class="text-gray-400 leading-relaxed text-center mb-10 max-w-3xl mx-auto">
                    Search and pick records based on mood. Browse and sort the full collection, or jump into random
                    releases.
                </p>

                <div class="mb-10">
                    <div class="flex items-center justify-between max-w-3xl mx-auto mb-6">
                        <h2 class="text-2xl font-bold tracking-[-0.01em] text-white">Pick a mood</h2>
                        <button
                            v-if="username"
                            type="button"
                            class="text-sm text-gray-400 hover:text-white transition-colors"
                            :aria-expanded="moodSectionOpen"
                            aria-controls="mood-section-panel"
                            @click="moodSectionOpen = !moodSectionOpen"
                        >
                            {{ moodSectionOpen ? 'Hide' : 'Show' }}
                        </button>
                    </div>

                    <div v-if="moodSectionOpen" id="mood-section-panel">
                        <form
                            v-if="username"
                            class="mb-2 flex flex-col sm:flex-row gap-3 max-w-3xl mx-auto w-full"
                            @submit.prevent="submitVibe"
                        >
                            <input
                                v-model="prompt"
                                type="text"
                                placeholder="e.g. dark moody post-punk for a late night drive"
                                class="flex-1 px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-oxblood-bright focus:ring-1 focus:ring-oxblood-bright"
                                :disabled="processing"
                            />
                            <button
                                type="submit"
                                :disabled="processing || !prompt.trim() || prompt.trim().length < 3"
                                class="inline-flex items-center justify-center rounded-lg bg-oxblood px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-oxblood-bright focus:outline-none focus:ring-2 focus:ring-oxblood-bright focus:ring-offset-2 focus:ring-offset-gray-950 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-oxblood"
                            >
                                <svg
                                    v-if="processing"
                                    class="motion-safe:animate-spin -ml-0.5 mr-2 h-4 w-4"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                    />
                                </svg>
                                {{ processing ? 'Finding...' : 'Find it' }}
                            </button>
                        </form>
                        <p v-if="vibeError" role="alert" class="text-center text-red-400 text-base mb-6">
                            {{ vibeError }}
                        </p>
                        <p v-else-if="username" class="text-center text-gray-400 text-base mb-6">
                            Uses Adam&apos;s collection — results may vary
                        </p>

                        <div v-if="!username" class="text-center py-12">
                            <div class="text-6xl mb-4" aria-hidden="true">⚫</div>
                            <h3 class="text-xl font-semibold text-gray-300 mb-2">No collection synced yet</h3>
                            <p class="text-gray-400 text-pretty mb-2">
                                Set
                                <code class="text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded text-sm"
                                    >DISCOGS_USERNAME</code
                                >
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
                            :class="{ 'pointer-events-none opacity-60 cursor-not-allowed': processing }"
                        >
                            <Link
                                v-for="mood in moods"
                                :key="mood.slug"
                                :href="route('mood.suggest', mood.slug)"
                                class="group flex flex-col items-center justify-center aspect-square sm:aspect-[4/3] bg-gray-900 border-2 border-gray-800 rounded-2xl p-6 text-center transition-all hover:border-gray-600 hover:bg-gray-800/80"
                            >
                                <span
                                    class="text-4xl sm:text-5xl mb-3 block group-hover:scale-110 transition-transform"
                                    >{{ mood.emoji }}</span
                                >
                                <span class="text-lg font-semibold text-white">{{ mood.label }}</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="mb-10 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link
                        v-if="username"
                        :href="route('collection.random')"
                        class="inline-flex items-center justify-center gap-2 min-w-[200px] px-5 py-2.5 bg-oxblood hover:bg-oxblood-bright border border-transparent rounded-lg text-white text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-oxblood-bright focus:ring-offset-2 focus:ring-offset-gray-950"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
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
                        class="inline-flex items-center justify-center min-w-[200px] px-5 py-2.5 bg-oxblood hover:bg-oxblood-bright border border-transparent rounded-lg text-white text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-oxblood-bright focus:ring-offset-2 focus:ring-offset-gray-950"
                    >
                        Browse collection
                    </Link>
                </div>

                <div v-if="username && insight" class="mt-8 rounded-xl bg-gray-900 border border-gray-800 p-6">
                    <h2 class="text-xl font-bold tracking-[-0.01em] text-white mb-3">Adam&apos;s music personality</h2>
                    <p class="text-gray-200 leading-relaxed text-pretty whitespace-pre-line">{{ insight }}</p>
                    <p class="text-xs text-gray-500 italic mt-4">A quick read on the collection, AI-generated.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    mood: { type: Object, default: () => ({}) },
    primary: { type: Object, default: () => ({}) },
    backups: { type: Array, default: () => [] },
});

const retrying = ref(false);

function tryAgain() {
    if (retrying.value) return;
    retrying.value = true;
    if (props.mood.vibePrompt) {
        router.post(route('vibe.suggest'), { prompt: props.mood.vibePrompt }, {
            preserveScroll: true,
            onFinish: () => { retrying.value = false; },
        });
    } else {
        router.visit(route('mood.suggest', props.mood.slug), {
            onFinish: () => { retrying.value = false; },
        });
    }
}
</script>

<template>
    <AppLayout>
        <Head :title="`${mood.label} – Discover`" />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <Link
                :href="route('home')"
                class="inline-flex items-center gap-2 text-gray-500 hover:text-white text-sm mb-8 transition-colors"
            >
                ← Different mood
            </Link>

            <div class="text-center mb-6">
                <span class="text-3xl">{{ mood.emoji }}</span>
                <h1 class="text-2xl font-bold text-white mt-2">{{ mood.label }}</h1>
            </div>

            <div class="flex flex-col md:flex-row gap-8 mb-10">
                <Link
                    :href="route('collection.show', primary.discogs_id)"
                    class="group block md:flex-shrink-0 md:w-2/5"
                >
                    <div
                        class="aspect-square bg-gray-800 rounded-xl overflow-hidden mb-4 shadow-xl group-hover:scale-[1.02] transition-transform"
                    >
                        <img
                            v-if="primary.cover_image"
                            :src="primary.cover_image"
                            :alt="primary.title"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-8xl text-gray-600">
                            ⚫
                        </div>
                    </div>
                    <h2 class="text-xl font-bold text-white truncate group-hover:underline">{{ primary.title }}</h2>
                    <p class="text-gray-400 truncate">{{ primary.artist }}</p>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <span
                            v-for="g in primary.genres"
                            :key="g"
                            class="px-2 py-0.5 bg-gray-800 text-gray-400 text-xs rounded-full"
                            >{{ g }}</span
                        >
                        <span
                            v-for="s in primary.styles"
                            :key="s"
                            class="px-2 py-0.5 bg-gray-700 text-gray-500 text-xs rounded-full"
                            >{{ s }}</span
                        >
                    </div>
                </Link>

                <div class="flex-1 flex flex-col justify-center">
                    <p class="text-gray-500 mb-6">
                        We picked this for you. Tap to view details, or try again for something different.
                    </p>
                    <button
                        type="button"
                        class="self-start px-5 py-2.5 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-lg text-white text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-gray-800"
                        :disabled="retrying"
                        @click="tryAgain"
                    >
                        {{ retrying ? 'Finding...' : 'Try again' }}
                    </button>
                </div>
            </div>

            <div v-if="backups.length" class="border-t border-gray-800 pt-8">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Also consider</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <Link
                        v-for="r in backups"
                        :key="r.discogs_id"
                        :href="route('collection.show', r.discogs_id)"
                        class="group block"
                    >
                        <div
                            class="relative aspect-square bg-gray-800 rounded-lg overflow-hidden mb-2 group-hover:scale-105 transition-transform"
                        >
                            <img
                                v-if="r.cover_image || r.thumb"
                                :src="r.cover_image || r.thumb"
                                :alt="r.title"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-4xl text-gray-600">
                                ⚫
                            </div>
                        </div>
                        <p class="text-white text-sm font-medium truncate">{{ r.title }}</p>
                        <p class="text-gray-500 text-xs truncate">{{ r.artist }}</p>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

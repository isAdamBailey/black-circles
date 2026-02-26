<script setup>
import { computed, ref } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    release: { type: Object, default: () => ({}) },
});

const imageIndex = ref(0);

const imageList = computed(() => {
    if (props.release.images && Array.isArray(props.release.images) && props.release.images.length > 0) {
        return props.release.images.map(img => img.uri || img).filter(Boolean);
    }
    if (props.release.cover_image) return [props.release.cover_image];
    return [];
});

const currentImage = computed(() => imageList.value[imageIndex.value] || null);

function prevImage() {
    if (imageList.value.length <= 1) return;
    imageIndex.value = (imageIndex.value - 1 + imageList.value.length) % imageList.value.length;
}

function nextImage() {
    if (imageList.value.length <= 1) return;
    imageIndex.value = (imageIndex.value + 1) % imageList.value.length;
}

function getYouTubeId(url) {
    const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/))([a-zA-Z0-9_-]{11})/);
    return match ? match[1] : null;
}

const youtubeVideos = computed(() => {
    if (!props.release.videos) return [];
    return props.release.videos
        .filter(v => v.uri && v.embed !== false && getYouTubeId(v.uri))
        .map(v => ({ ...v, youtubeId: getYouTubeId(v.uri) }));
});

const genres = computed(() => {
    if (!props.release.genres) return [];
    return Array.isArray(props.release.genres) ? props.release.genres : JSON.parse(props.release.genres);
});

const styles = computed(() => {
    if (!props.release.styles) return [];
    return Array.isArray(props.release.styles) ? props.release.styles : JSON.parse(props.release.styles);
});

const tracklist = computed(() => {
    if (!props.release.tracklist) return [];
    return Array.isArray(props.release.tracklist) ? props.release.tracklist : JSON.parse(props.release.tracklist);
});

const formats = computed(() => {
    if (!props.release.formats) return [];
    return Array.isArray(props.release.formats) ? props.release.formats : JSON.parse(props.release.formats);
});
</script>

<template>
    <AppLayout>
        <Head :title="release.title" />

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Back -->
            <Link :href="route('collection.index')" class="inline-flex items-center gap-2 text-gray-500 hover:text-white text-sm mb-8 transition-colors">
                ← Back to collection
            </Link>

            <!-- Main layout -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-8 mb-10">
                <!-- Cover Art / Image slider -->
                <div class="md:col-span-2">
                    <div class="aspect-square bg-gray-800 rounded-xl overflow-hidden shadow-2xl relative group">
                        <img
                            v-if="currentImage"
                            :src="currentImage"
                            :alt="release.title"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-8xl text-gray-600">⚫</div>
                        <template v-if="imageList.length > 1">
                            <button
                                type="button"
                                class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                aria-label="Previous image"
                                @click="prevImage"
                            >
                                ‹
                            </button>
                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                aria-label="Next image"
                                @click="nextImage"
                            >
                                ›
                            </button>
                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1.5">
                                <button
                                    v-for="(_, i) in imageList"
                                    :key="i"
                                    type="button"
                                    class="w-2 h-2 rounded-full transition-colors"
                                    :class="i === imageIndex ? 'bg-white' : 'bg-white/40 hover:bg-white/60'"
                                    :aria-label="`Image ${i + 1}`"
                                    @click="imageIndex = i"
                                />
                            </div>
                        </template>
                    </div>
                    <!-- Price info -->
                    <div v-if="release.lowest_price != null" class="mt-4 bg-gray-900 rounded-xl p-4 border border-gray-800">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Lowest for sale</h3>
                        <div class="text-xl font-bold text-green-400">${{ Number(release.lowest_price).toFixed(2) }}</div>
                    </div>
                </div>

                <!-- Release Info -->
                <div class="md:col-span-3">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span v-for="genre in genres" :key="genre" class="px-2.5 py-1 bg-gray-800 text-gray-300 text-xs rounded-full">{{ genre }}</span>
                        <span v-for="style in styles" :key="style" class="px-2.5 py-1 bg-gray-700 text-gray-400 text-xs rounded-full">{{ style }}</span>
                    </div>

                    <h1 class="text-3xl font-bold text-white leading-tight mb-2">{{ release.title }}</h1>
                    <p class="text-xl text-gray-300 mb-6">{{ release.artist }}</p>

                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div v-if="release.label">
                            <dt class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Label</dt>
                            <dd class="text-white">{{ release.label }}</dd>
                        </div>
                        <div v-if="release.year && release.year !== 0">
                            <dt class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Year</dt>
                            <dd class="text-white">{{ release.year }}</dd>
                        </div>
                        <div v-if="release.catalog_number">
                            <dt class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Cat #</dt>
                            <dd class="text-white">{{ release.catalog_number }}</dd>
                        </div>
                        <div v-if="formats.length">
                            <dt class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Format</dt>
                            <dd class="text-white">{{ formats.map(f => f.name).join(', ') }}</dd>
                        </div>
                        <div v-if="release.collection_item?.rating">
                            <dt class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">My Rating</dt>
                            <dd class="text-yellow-400">{{ '★'.repeat(release.collection_item.rating) }}{{ '☆'.repeat(5 - release.collection_item.rating) }}</dd>
                        </div>
                        <div v-if="release.collection_item?.date_added">
                            <dt class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Added</dt>
                            <dd class="text-white">{{ new Date(release.collection_item.date_added).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</dd>
                        </div>
                    </dl>

                    <div v-if="release.notes" class="mt-6 p-4 bg-gray-900 rounded-lg border border-gray-800">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Notes</h3>
                        <p class="text-gray-300 text-sm leading-relaxed">{{ release.notes }}</p>
                    </div>

                    <a
                        v-if="release.discogs_uri"
                        :href="release.discogs_uri"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 mt-6 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white text-sm rounded-lg transition-colors"
                    >
                        View on Discogs ↗
                    </a>
                </div>
            </div>

            <!-- Tracklist -->
            <div v-if="tracklist.length" class="mb-10">
                <h2 class="text-lg font-bold text-white mb-4">Tracklist</h2>
                <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
                    <div
                        v-for="(track, index) in tracklist"
                        :key="index"
                        class="flex items-center gap-4 px-5 py-3 border-b border-gray-800 last:border-b-0 hover:bg-gray-800/50 transition-colors"
                        :class="track.type_ === 'heading' ? 'bg-gray-800/30' : ''"
                    >
                        <template v-if="track.type_ !== 'heading'">
                            <span class="text-gray-600 text-xs w-8 shrink-0 text-right">{{ track.position }}</span>
                            <span class="text-white text-sm flex-1">{{ track.title }}</span>
                            <span v-if="track.duration" class="text-gray-500 text-xs shrink-0">{{ track.duration }}</span>
                        </template>
                        <template v-else>
                            <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider">{{ track.title }}</span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Videos -->
            <div v-if="youtubeVideos.length" class="mb-10">
                <h2 class="text-lg font-bold text-white mb-4">Videos</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="video in youtubeVideos" :key="video.youtubeId" class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
                        <div class="relative aspect-video">
                            <iframe
                                :src="`https://www.youtube.com/embed/${video.youtubeId}`"
                                :title="video.title"
                                class="w-full h-full"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </div>
                        <div v-if="video.title" class="px-4 py-3">
                            <p class="text-gray-300 text-sm">{{ video.title }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

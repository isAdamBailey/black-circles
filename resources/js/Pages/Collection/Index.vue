<script setup>
import { ref, watch } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    releases: Object,
    filters: Object,
    allGenres: Array,
    allStyles: Array,
    username: String,
    lastSynced: String,
});

const search = ref(props.filters.search ?? '');
const selectedGenres = ref(props.filters.genres ? (Array.isArray(props.filters.genres) ? props.filters.genres : [props.filters.genres]) : []);
const selectedStyles = ref(props.filters.styles ? (Array.isArray(props.filters.styles) ? props.filters.styles : [props.filters.styles]) : []);
const sort = ref(props.filters.sort ?? 'date_added');
const direction = ref(props.filters.direction ?? 'desc');
const showFilters = ref(false);

let searchTimeout = null;

function applyFilters() {
    router.get(route('collection.index'), {
        search: search.value || undefined,
        genres: selectedGenres.value.length ? selectedGenres.value : undefined,
        styles: selectedStyles.value.length ? selectedStyles.value : undefined,
        sort: sort.value,
        direction: direction.value,
    }, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch([selectedGenres, selectedStyles, sort, direction], applyFilters, { deep: true });

function toggleGenre(genre) {
    const idx = selectedGenres.value.indexOf(genre);
    if (idx >= 0) selectedGenres.value.splice(idx, 1);
    else selectedGenres.value.push(genre);
}

function toggleStyle(style) {
    const idx = selectedStyles.value.indexOf(style);
    if (idx >= 0) selectedStyles.value.splice(idx, 1);
    else selectedStyles.value.push(style);
}

function clearFilters() {
    search.value = '';
    selectedGenres.value = [];
    selectedStyles.value = [];
    sort.value = 'date_added';
    direction.value = 'desc';
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

const sortOptions = [
    { value: 'date_added', label: 'Date Added' },
    { value: 'title', label: 'Title' },
    { value: 'artist', label: 'Artist' },
    { value: 'year', label: 'Year' },
    { value: 'value', label: 'Value' },
];
</script>

<template>
    <AppLayout>
        <Head title="Collection" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-white">
                        Vinyl Collection
                        <span v-if="username" class="text-gray-400 text-lg font-normal ml-2">@{{ username }}</span>
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">
                        {{ releases.total }} records
                        <span v-if="lastSynced"> · Last synced {{ formatDate(lastSynced) }}</span>
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('settings.index')"
                        class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm rounded-lg transition-colors"
                    >
                        ⚙ Settings
                    </Link>
                </div>
            </div>

            <!-- No username state -->
            <div v-if="!username" class="text-center py-24">
                <div class="text-6xl mb-4">⚫</div>
                <h2 class="text-xl font-semibold text-gray-300 mb-2">No collection synced yet</h2>
                <p class="text-gray-500 mb-6">Add your Discogs username in Settings to get started.</p>
                <Link :href="route('settings.index')" class="px-6 py-3 bg-white text-black font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                    Go to Settings
                </Link>
            </div>

            <template v-else>
                <!-- Search + Sort bar -->
                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                    <div class="relative flex-1">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search titles, artists, labels…"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-gray-500 text-sm"
                        />
                        <button v-if="search" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">✕</button>
                    </div>
                    <select
                        v-model="sort"
                        class="bg-gray-900 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-gray-500"
                    >
                        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <button
                        @click="direction = direction === 'asc' ? 'desc' : 'asc'"
                        class="px-3 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-300 hover:text-white text-sm transition-colors"
                        :title="direction === 'asc' ? 'Ascending' : 'Descending'"
                    >
                        {{ direction === 'asc' ? '↑' : '↓' }}
                    </button>
                    <button
                        @click="showFilters = !showFilters"
                        class="px-4 py-2.5 bg-gray-900 border rounded-lg text-sm transition-colors"
                        :class="(selectedGenres.length || selectedStyles.length) ? 'border-white text-white' : 'border-gray-700 text-gray-400 hover:text-white'"
                    >
                        Filter {{ selectedGenres.length + selectedStyles.length > 0 ? `(${selectedGenres.length + selectedStyles.length})` : '' }}
                    </button>
                </div>

                <!-- Filters panel -->
                <div v-if="showFilters" class="bg-gray-900 border border-gray-800 rounded-xl p-5 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider">Filters</h3>
                        <button @click="clearFilters" class="text-xs text-gray-500 hover:text-gray-300 transition-colors">Clear all</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-if="allGenres.length">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Genres</h4>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="genre in allGenres"
                                    :key="genre"
                                    @click="toggleGenre(genre)"
                                    class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                                    :class="selectedGenres.includes(genre)
                                        ? 'bg-white text-black'
                                        : 'bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700'"
                                >
                                    {{ genre }}
                                </button>
                            </div>
                        </div>
                        <div v-if="allStyles.length">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Styles</h4>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="style in allStyles"
                                    :key="style"
                                    @click="toggleStyle(style)"
                                    class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                                    :class="selectedStyles.includes(style)
                                        ? 'bg-white text-black'
                                        : 'bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700'"
                                >
                                    {{ style }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="releases.data.length === 0" class="text-center py-24">
                    <div class="text-5xl mb-4">🔍</div>
                    <h2 class="text-lg font-semibold text-gray-300 mb-2">No records found</h2>
                    <p class="text-gray-500 text-sm">Try adjusting your search or filters.</p>
                    <button @click="clearFilters" class="mt-4 text-sm text-gray-400 hover:text-white underline">Clear filters</button>
                </div>

                <!-- Album Grid -->
                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <Link
                        v-for="release in releases.data"
                        :key="release.id"
                        :href="route('collection.show', release.discogs_id)"
                        class="group block"
                    >
                        <div class="relative aspect-square bg-gray-800 rounded-lg overflow-hidden mb-2">
                            <img
                                v-if="release.cover_image"
                                :src="release.cover_image"
                                :alt="release.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-4xl text-gray-600">⚫</div>
                            <!-- Rating badge -->
                            <div v-if="release.collection_item?.rating" class="absolute top-2 right-2 bg-black/70 rounded-full px-2 py-0.5 text-xs text-yellow-400">
                                {{ '★'.repeat(release.collection_item.rating) }}
                            </div>
                            <!-- Hover overlay -->
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors duration-300 flex items-end p-2 opacity-0 group-hover:opacity-100">
                                <span class="text-white text-xs font-medium truncate">View details</span>
                            </div>
                        </div>
                        <div class="px-0.5">
                            <p class="text-white text-xs font-semibold truncate leading-tight">{{ release.title }}</p>
                            <p class="text-gray-400 text-xs truncate mt-0.5">{{ release.artist }}</p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-gray-600 text-xs">{{ release.year }}</span>
                                <span v-if="release.median_price" class="text-green-400 text-xs">${{ Number(release.median_price).toFixed(2) }}</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="releases.last_page > 1" class="flex justify-center gap-2 mt-10">
                    <template v-for="link in releases.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1.5 rounded text-sm transition-colors"
                            :class="link.active ? 'bg-white text-black font-semibold' : 'bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-1.5 rounded text-sm text-gray-600 bg-gray-900"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { router, Link, Head, InfiniteScroll } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    releases: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    allGenres: { type: Array, default: () => [] },
    allStyles: { type: Array, default: () => [] },
    username: { type: String, default: '' },
    lastSynced: { type: String, default: '' },
});

const search = ref(props.filters.search ?? '');
const selectedGenres = ref(props.filters.genres ? (Array.isArray(props.filters.genres) ? props.filters.genres : [props.filters.genres]) : []);
const selectedStyles = ref(props.filters.styles ? (Array.isArray(props.filters.styles) ? props.filters.styles : [props.filters.styles]) : []);
const sort = ref(props.filters.sort ?? 'date_added');
const direction = ref(props.filters.direction ?? 'desc');
const showFilters = ref(false);
const suggestions = ref([]);
const showSuggestions = ref(false);
const fetchingSuggestions = ref(false);
const searchWrapperRef = ref(null);

let searchTimeout = null;
let suggestionTimeout = null;

function applyFilters() {
    router.get(route('collection.index'), {
        search: search.value || undefined,
        genres: selectedGenres.value.length ? selectedGenres.value : undefined,
        styles: selectedStyles.value.length ? selectedStyles.value : undefined,
        sort: sort.value,
        direction: direction.value,
    }, { preserveState: true, replace: true, reset: ['releases'] });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);

    clearTimeout(suggestionTimeout);
    if (search.value.trim().length >= 2) {
        suggestionTimeout = setTimeout(fetchSuggestions, 150);
    } else {
        suggestions.value = [];
        showSuggestions.value = false;
    }
});

watch([selectedGenres, selectedStyles, sort, direction], applyFilters, { deep: true });

async function fetchSuggestions() {
    const q = search.value.trim();
    if (q.length < 2) return;
    fetchingSuggestions.value = true;
    try {
        const { data } = await axios.get(route('collection.search'), { params: { q } });
        suggestions.value = data.data ?? [];
        showSuggestions.value = suggestions.value.length > 0;
    } catch {
        suggestions.value = [];
        showSuggestions.value = false;
    } finally {
        fetchingSuggestions.value = false;
    }
}

function selectSuggestion(release) {
    showSuggestions.value = false;
    suggestions.value = [];
    router.visit(route('collection.show', release.discogs_id));
}

function handleClickOutside(event) {
    if (searchWrapperRef.value && !searchWrapperRef.value.contains(event.target)) {
        showSuggestions.value = false;
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

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

const releasesData = computed(() => props.releases?.data ?? []);
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
            </div>

            <!-- No username state -->
            <div v-if="!username" class="text-center py-24">
                <div class="text-6xl mb-4">⚫</div>
                <h2 class="text-xl font-semibold text-gray-300 mb-2">No collection synced yet</h2>
                <p class="text-gray-500 mb-6">
                    Set <code class="text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded text-sm">DISCOGS_USERNAME</code> in .env and run <code class="text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded text-sm">sail artisan discogs:sync</code> to get started.
                </p>
            </div>

            <template v-else>
                <!-- Search + Sort bar -->
                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                    <div ref="searchWrapperRef" class="relative flex-1">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search titles, artists, labels…"
                            autocomplete="off"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-gray-500 text-sm"
                        />
                        <button v-if="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300" @click="search = ''">✕</button>
                        <div
                            v-if="showSuggestions && (suggestions.length || fetchingSuggestions)"
                            class="absolute top-full left-0 right-0 mt-1 bg-gray-900 border border-gray-700 rounded-lg shadow-xl z-50 max-h-64 overflow-auto"
                        >
                            <div v-if="fetchingSuggestions" class="px-4 py-3 text-gray-500 text-sm">Searching…</div>
                            <button
                                v-for="r in suggestions"
                                :key="r.id"
                                type="button"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-gray-800 transition-colors"
                                @click="selectSuggestion(r)"
                            >
                                <img v-if="r.thumb" :src="r.thumb" alt="" class="w-10 h-10 rounded object-cover shrink-0" />
                                <div v-else class="w-10 h-10 rounded bg-gray-700 shrink-0 flex items-center justify-center text-gray-500 text-lg">⚫</div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-white text-sm font-medium truncate">{{ r.title }}</div>
                                    <div class="text-gray-400 text-xs truncate">{{ r.artist }}</div>
                                </div>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <select
                            v-model="sort"
                            class="bg-gray-900 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-gray-500"
                        >
                            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <p v-if="sort === 'value'" class="text-gray-500 text-xs">
                            Value = lowest listed. Many have no price until you open them or until copies are for sale.
                        </p>
                    </div>
                    <button
                        class="px-3 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-300 hover:text-white text-sm transition-colors"
                        :title="direction === 'asc' ? 'Ascending' : 'Descending'"
                        @click="direction = direction === 'asc' ? 'desc' : 'asc'"
                    >
                        {{ direction === 'asc' ? '↑' : '↓' }}
                    </button>
                    <button
                        class="px-4 py-2.5 bg-gray-900 border rounded-lg text-sm transition-colors"
                        :class="(selectedGenres.length || selectedStyles.length) ? 'border-white text-white' : 'border-gray-700 text-gray-400 hover:text-white'"
                        @click="showFilters = !showFilters"
                    >
                        Filter {{ selectedGenres.length + selectedStyles.length > 0 ? `(${selectedGenres.length + selectedStyles.length})` : '' }}
                    </button>
                </div>

                <!-- Filters panel -->
                <div v-if="showFilters" class="bg-gray-900 border border-gray-800 rounded-xl p-5 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider">Filters</h3>
                        <button class="text-xs text-gray-500 hover:text-gray-300 transition-colors" @click="clearFilters">Clear all</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-if="allGenres.length">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Genres</h4>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="genre in allGenres"
                                    :key="genre"
                                    class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                                    :class="selectedGenres.includes(genre)
                                        ? 'bg-white text-black'
                                        : 'bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700'"
                                    @click="toggleGenre(genre)"
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
                                    class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                                    :class="selectedStyles.includes(style)
                                        ? 'bg-white text-black'
                                        : 'bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700'"
                                    @click="toggleStyle(style)"
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
                    <button class="mt-4 text-sm text-gray-400 hover:text-white underline" @click="clearFilters">Clear filters</button>
                </div>

                <!-- Album Grid -->
                <InfiniteScroll v-else data="releases" only-next :buffer="300">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <Link
                            v-for="release in releasesData"
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
                                <div v-if="release.collection_item?.rating" class="absolute top-2 right-2 bg-black/70 rounded-full px-2 py-0.5 text-xs text-yellow-400">
                                    {{ '★'.repeat(release.collection_item.rating) }}
                                </div>
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors duration-300 flex items-end p-2 opacity-0 group-hover:opacity-100">
                                    <span class="text-white text-xs font-medium truncate">View details</span>
                                </div>
                            </div>
                            <div class="px-0.5">
                                <p class="text-white text-xs font-semibold truncate leading-tight">{{ release.title }}</p>
                                <p class="text-gray-400 text-xs truncate mt-0.5">{{ release.artist }}</p>
                                <div class="flex items-center justify-between mt-1 gap-1">
                                    <span class="text-gray-600 text-xs shrink-0">{{ release.year && release.year !== 0 ? release.year : '—' }}</span>
                                    <div v-if="release.lowest_price != null" class="text-right text-xs text-gray-400 shrink min-w-0">
                                        <span class="text-green-400">${{ Number(release.lowest_price).toFixed(0) }}</span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </InfiniteScroll>
            </template>
        </div>
    </AppLayout>
</template>

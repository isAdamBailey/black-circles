<script setup lang="ts">
import type { ApiEnvelope, CollectionIndex, SearchSuggestion } from '~/types/api'

const { get } = useApi()

const search = ref('')
const debouncedSearch = ref('')
const selectedGenres = ref<string[]>([])
const selectedStyles = ref<string[]>([])
const sort = ref('value')
const direction = ref<'asc' | 'desc'>('desc')
const showFilters = ref(false)

const sortOptions = [
  { value: 'date_added', label: 'Date Added' },
  { value: 'title', label: 'Title' },
  { value: 'artist', label: 'Artist' },
  { value: 'year', label: 'Year' },
  { value: 'value', label: 'Value' },
]

function buildQuery(pageNum: number): Record<string, unknown> {
  const query: Record<string, unknown> = {
    sort: sort.value,
    direction: direction.value,
    page: pageNum,
  }
  if (debouncedSearch.value) query.search = debouncedSearch.value
  if (selectedGenres.value.length) query['genres[]'] = selectedGenres.value
  if (selectedStyles.value.length) query['styles[]'] = selectedStyles.value
  return query
}

const {
  data,
  status,
  error,
  refresh,
} = useAsyncData<CollectionIndex>(
  'collection-index',
  () => get<CollectionIndex>('/collection', { query: buildQuery(1) }),
  { lazy: true, watch: [debouncedSearch, selectedGenres, selectedStyles, sort, direction] },
)

const extraReleases = ref<CollectionIndex['data']>([])
const currentPage = ref(1)
const loadingMore = ref(false)

watch(data, () => {
  extraReleases.value = []
  currentPage.value = 1
})

const releases = computed(() => [...(data.value?.data ?? []), ...extraReleases.value])
const meta = computed(() => data.value?.meta)
const hasMore = computed(() => !!meta.value && currentPage.value < meta.value.last_page)

async function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  loadingMore.value = true
  try {
    const next = currentPage.value + 1
    const response = await get<CollectionIndex>('/collection', { query: buildQuery(next) })
    extraReleases.value = [...extraReleases.value, ...response.data]
    currentPage.value = next
  } catch {
    // Load more failed silently — the button stays put so the user can retry.
  } finally {
    loadingMore.value = false
  }
}

const sentinelRef = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | undefined

onMounted(() => {
  if (typeof IntersectionObserver === 'undefined') return
  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0]?.isIntersecting) loadMore()
    },
    { rootMargin: '300px' },
  )
  if (sentinelRef.value) observer.observe(sentinelRef.value)
})

onUnmounted(() => observer?.disconnect())

let searchTimeout: ReturnType<typeof setTimeout> | undefined
let suggestionTimeout: ReturnType<typeof setTimeout> | undefined

const suggestions = ref<SearchSuggestion[]>([])
const showSuggestions = ref(false)
const fetchingSuggestions = ref(false)
const searchWrapperRef = ref<HTMLElement | null>(null)

watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    debouncedSearch.value = search.value.trim()
  }, 400)

  clearTimeout(suggestionTimeout)
  if (search.value.trim().length >= 2) {
    suggestionTimeout = setTimeout(fetchSuggestions, 150)
  } else {
    suggestions.value = []
    showSuggestions.value = false
  }
})

async function fetchSuggestions() {
  const q = search.value.trim()
  if (q.length < 2) return
  fetchingSuggestions.value = true
  try {
    const response = await get<ApiEnvelope<SearchSuggestion[]>>('/collection/search', { query: { q } })
    suggestions.value = response.data
    showSuggestions.value = suggestions.value.length > 0
  } catch {
    suggestions.value = []
    showSuggestions.value = false
  } finally {
    fetchingSuggestions.value = false
  }
}

function selectSuggestion(release: SearchSuggestion) {
  showSuggestions.value = false
  suggestions.value = []
  navigateTo(`/collection/${release.discogs_id}`)
}

function handleClickOutside(event: MouseEvent) {
  if (searchWrapperRef.value && !searchWrapperRef.value.contains(event.target as Node)) {
    showSuggestions.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

function toggleGenre(genre: string) {
  selectedGenres.value = selectedGenres.value.includes(genre)
    ? selectedGenres.value.filter((g) => g !== genre)
    : [...selectedGenres.value, genre]
}

function toggleStyle(style: string) {
  selectedStyles.value = selectedStyles.value.includes(style)
    ? selectedStyles.value.filter((s) => s !== style)
    : [...selectedStyles.value, style]
}

function clearFilters() {
  search.value = ''
  debouncedSearch.value = ''
  selectedGenres.value = []
  selectedStyles.value = []
  sort.value = 'value'
  direction.value = 'desc'
}

function formatDate(dateStr: string | null | undefined) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

useHead({ title: 'Collection' })
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-bold text-pressing">Adam&apos;s Vinyl Collection</h1>
        <p v-if="meta" class="text-dust text-sm mt-1">
          {{ meta.total }} records in Adam&apos;s collection
          <span v-if="data?.lastSynced"> · Last synced {{ formatDate(data.lastSynced) }}</span>
        </p>
      </div>
    </div>

    <div v-if="status === 'pending'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
      <div
        v-for="n in 12"
        :key="n"
        class="aspect-square bg-cabinet border-2 border-shelf rounded-lg motion-safe:animate-pulse"
      />
    </div>

    <div v-else-if="error" class="text-center py-24" role="alert">
      <h2 class="text-xl font-semibold text-sleeve mb-2">Couldn&apos;t load the collection</h2>
      <p class="text-label text-pretty mb-4">The API didn&apos;t respond. Check that the backend is running, then try again.</p>
      <button
        type="button"
        class="inline-flex items-center justify-center rounded-md bg-shelf hover:bg-groove border border-jacket px-5 py-2.5 text-sm font-semibold text-pressing transition-colors"
        @click="refresh()"
      >
        Try again
      </button>
    </div>

    <div v-else-if="!data?.username" class="text-center py-24">
      <div class="text-6xl mb-4" aria-hidden="true">⚫</div>
      <h2 class="text-xl font-semibold text-sleeve mb-2">No collection synced yet</h2>
      <p class="text-label text-pretty mb-6">
        Set <code class="text-label bg-shelf px-1.5 py-0.5 rounded text-sm">DISCOGS_USERNAME</code> in .env and run
        <code class="text-label bg-shelf px-1.5 py-0.5 rounded text-sm">sail artisan discogs:sync</code>
        to get started.
      </p>
    </div>

    <template v-else>
      <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div ref="searchWrapperRef" class="relative flex-1">
          <input
            v-model="search"
            type="text"
            placeholder="Search Adam's titles, artists, labels…"
            autocomplete="off"
            class="w-full bg-cabinet border border-groove rounded-lg px-4 py-2.5 text-pressing placeholder-dust focus:outline-none focus:border-dust text-sm"
          >
          <button
            v-if="search"
            type="button"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-dust hover:text-sleeve"
            @click="search = ''"
          >
            ✕
          </button>
          <div
            v-if="showSuggestions && (suggestions.length || fetchingSuggestions)"
            class="absolute top-full left-0 right-0 mt-1 bg-cabinet border border-groove rounded-lg shadow-xl z-50 max-h-64 overflow-auto"
          >
            <div v-if="fetchingSuggestions" class="px-4 py-3 text-dust text-sm">Searching…</div>
            <button
              v-for="r in suggestions"
              :key="r.id"
              type="button"
              class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-shelf transition-colors"
              @click="selectSuggestion(r)"
            >
              <img
                v-if="r.thumb"
                :src="r.thumb"
                alt=""
                class="w-10 h-10 rounded object-cover shrink-0"
              >
              <div
                v-else
                class="w-10 h-10 rounded bg-groove shrink-0 flex items-center justify-center text-dust text-lg"
              >
                ⚫
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-pressing text-sm font-medium truncate">{{ r.title }}</div>
                <div class="text-label text-xs truncate">{{ r.artist }}</div>
              </div>
            </button>
          </div>
        </div>
        <div class="flex flex-col gap-1">
          <select
            v-model="sort"
            class="bg-cabinet border border-groove rounded-lg px-3 py-2.5 text-pressing text-sm focus:outline-none focus:border-dust"
          >
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
          <p v-if="sort === 'value'" class="text-dust text-xs">Lowest listed price</p>
        </div>
        <button
          type="button"
          class="px-3 py-2.5 bg-cabinet border border-groove rounded-lg text-label hover:text-pressing text-sm transition-colors"
          :title="direction === 'asc' ? 'Ascending' : 'Descending'"
          @click="direction = direction === 'asc' ? 'desc' : 'asc'"
        >
          {{ direction === 'asc' ? '↑' : '↓' }}
        </button>
        <button
          type="button"
          class="px-4 py-2.5 bg-cabinet border rounded-lg text-sm transition-colors"
          :class="
            selectedGenres.length || selectedStyles.length
              ? 'border-pressing text-pressing'
              : 'border-groove text-label hover:text-pressing'
          "
          @click="showFilters = !showFilters"
        >
          Filter
          {{
            selectedGenres.length + selectedStyles.length > 0
              ? `(${selectedGenres.length + selectedStyles.length})`
              : ''
          }}
        </button>
      </div>

      <div v-if="showFilters" class="bg-cabinet border border-shelf rounded-xl p-5 mb-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-sm font-semibold text-label uppercase tracking-wider">Filters</h3>
          <button type="button" class="text-xs text-dust hover:text-sleeve transition-colors" @click="clearFilters">
            Clear all
          </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div v-if="data?.allGenres?.length">
            <h4 class="text-xs font-semibold text-dust uppercase tracking-wider mb-3">Genres</h4>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="genre in data.allGenres"
                :key="genre"
                type="button"
                class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                :class="
                  selectedGenres.includes(genre)
                    ? 'bg-pressing text-void'
                    : 'bg-shelf text-label hover:text-pressing hover:bg-groove'
                "
                @click="toggleGenre(genre)"
              >
                {{ genre }}
              </button>
            </div>
          </div>
          <div v-if="data?.allStyles?.length">
            <h4 class="text-xs font-semibold text-dust uppercase tracking-wider mb-3">Styles</h4>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="style in data.allStyles"
                :key="style"
                type="button"
                class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                :class="
                  selectedStyles.includes(style)
                    ? 'bg-pressing text-void'
                    : 'bg-shelf text-label hover:text-pressing hover:bg-groove'
                "
                @click="toggleStyle(style)"
              >
                {{ style }}
              </button>
            </div>
          </div>
        </div>
        <div class="mt-4 pt-4 border-t border-shelf">
          <button
            type="button"
            class="w-full sm:w-auto px-4 py-2.5 bg-pressing text-void font-medium rounded-lg hover:bg-sleeve transition-colors text-sm"
            @click="showFilters = false"
          >
            Find results
          </button>
        </div>
      </div>

      <div v-if="releases.length === 0" class="text-center py-24">
        <div class="text-5xl mb-4" aria-hidden="true">🔍</div>
        <h2 class="text-lg font-semibold text-sleeve mb-2">No records found</h2>
        <p class="text-dust text-sm">Try adjusting your search or filters.</p>
        <button type="button" class="mt-4 text-sm text-label hover:text-pressing underline" @click="clearFilters">
          Clear filters
        </button>
      </div>

      <template v-else>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <NuxtLink
            v-for="release in releases"
            :key="release.id"
            :to="`/collection/${release.discogs_id}`"
            class="group block"
          >
            <div class="relative aspect-square bg-shelf rounded-lg overflow-hidden mb-2">
              <img
                v-if="release.cover_image"
                :src="release.cover_image"
                :alt="release.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
              >
              <div v-else class="w-full h-full flex items-center justify-center text-4xl text-jacket">
                ⚫
              </div>
              <div
                v-if="release.collection_item?.rating"
                class="absolute top-2 right-2 bg-void/70 rounded-full px-2 py-0.5 text-xs text-yellow-400"
              >
                {{ '★'.repeat(release.collection_item.rating) }}
              </div>
              <div
                class="absolute inset-0 bg-void/0 group-hover:bg-void/40 transition-colors duration-300 flex items-end p-2 opacity-0 group-hover:opacity-100"
              >
                <span class="text-pressing text-xs font-medium truncate">View details</span>
              </div>
            </div>
            <div class="px-0.5">
              <p class="text-pressing text-xs font-semibold truncate leading-tight">
                {{ release.title }}
              </p>
              <p class="text-label text-xs truncate mt-0.5">{{ release.artist }}</p>
              <div class="flex items-center justify-between mt-1 gap-1">
                <span class="text-dust text-xs shrink-0">{{
                  release.year && release.year !== 0 ? release.year : '—'
                }}</span>
                <div v-if="release.lowest_price != null" class="text-right text-xs text-label shrink min-w-0">
                  <span class="text-green-400">${{ Number(release.lowest_price).toFixed(0) }}</span>
                </div>
              </div>
            </div>
          </NuxtLink>
        </div>

        <div v-if="hasMore" ref="sentinelRef" class="flex justify-center py-8">
          <button
            type="button"
            class="px-5 py-2.5 bg-cabinet border border-groove rounded-lg text-sm text-label hover:text-pressing transition-colors disabled:opacity-50"
            :disabled="loadingMore"
            @click="loadMore"
          >
            {{ loadingMore ? 'Loading…' : 'Load more' }}
          </button>
        </div>
      </template>
    </template>
  </div>
</template>


<script setup lang="ts">
import type { ApiEnvelope, Release } from '~/types/api'

const route = useRoute()
const { get } = useApi()

const id = computed(() => String(route.params.id))

const { data, error } = await useAsyncData(
  () => `release-${id.value}`,
  () => get<ApiEnvelope<Release>>(`/collection/${id.value}`),
  { watch: [id] },
)

const release = computed(() => data.value?.data ?? null)
const notFound = computed(() => error.value?.statusCode === 404)

const imageIndex = ref(0)

watch(release, () => {
  imageIndex.value = 0
})

const imageList = computed(() => {
  const images = release.value?.images
  if (images && images.length > 0) {
    return images.map((img) => (typeof img === 'string' ? img : img.uri)).filter((uri): uri is string => !!uri)
  }
  if (release.value?.cover_image) return [release.value.cover_image]
  return []
})

const currentImage = computed(() => imageList.value[imageIndex.value] ?? null)

function prevImage() {
  if (imageList.value.length <= 1) return
  imageIndex.value = (imageIndex.value - 1 + imageList.value.length) % imageList.value.length
}

function nextImage() {
  if (imageList.value.length <= 1) return
  imageIndex.value = (imageIndex.value + 1) % imageList.value.length
}

function getYouTubeId(url: string) {
  const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/))([a-zA-Z0-9_-]{11})/)
  return match ? match[1] : null
}

const youtubeVideos = computed(() => {
  const videos = release.value?.videos ?? []
  return videos
    .filter((v) => v.uri && v.embed !== false && getYouTubeId(v.uri))
    .map((v) => ({ ...v, youtubeId: getYouTubeId(v.uri) as string }))
})

const formatsLabel = computed(() => (release.value?.formats ?? []).map((f) => f.name).join(', '))

useHead(() => ({ title: release.value?.title ?? 'Release' }))
</script>

<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <NuxtLink
      to="/collection"
      class="inline-flex items-center gap-2 text-dust hover:text-pressing text-sm mb-8 transition-colors"
    >
      ← Back to collection
    </NuxtLink>

    <div v-if="notFound" class="text-center py-12">
      <div class="text-6xl mb-4" aria-hidden="true">⚫</div>
      <h1 class="text-xl font-semibold text-sleeve mb-2">Release not found</h1>
      <p class="text-label text-pretty">That release isn&apos;t in the collection.</p>
    </div>

    <div v-else-if="error" class="text-center py-12" role="alert">
      <h1 class="text-xl font-semibold text-sleeve mb-2">Couldn&apos;t load this release</h1>
      <p class="text-label text-pretty">The API didn&apos;t respond. Check that the backend is running, then try again.</p>
    </div>

    <template v-else-if="release">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-8 mb-10">
        <div class="md:col-span-2">
          <div class="aspect-square bg-shelf rounded-xl overflow-hidden shadow-2xl relative group">
            <img
              v-if="currentImage"
              :src="currentImage"
              :alt="release.title"
              class="w-full h-full object-cover"
            >
            <div v-else class="w-full h-full flex items-center justify-center text-8xl text-jacket">⚫</div>
            <template v-if="imageList.length > 1">
              <button
                type="button"
                class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-void/60 hover:bg-void/80 text-pressing flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                aria-label="Previous image"
                @click="prevImage"
              >
                ‹
              </button>
              <button
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-void/60 hover:bg-void/80 text-pressing flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
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
                  :class="i === imageIndex ? 'bg-pressing' : 'bg-pressing/40 hover:bg-pressing/60'"
                  :aria-label="`Image ${i + 1}`"
                  @click="imageIndex = i"
                />
              </div>
            </template>
          </div>
          <div v-if="release.lowest_price != null" class="mt-4 bg-cabinet rounded-xl p-4 border border-shelf">
            <h3 class="text-xs font-semibold text-dust uppercase tracking-wider mb-1">Lowest listed price</h3>
            <div class="text-xl font-bold text-green-400">${{ Number(release.lowest_price).toFixed(2) }}</div>
          </div>
        </div>

        <div class="md:col-span-3">
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <NuxtLink
              v-for="genre in release.genres ?? []"
              :key="genre"
              :to="{ path: '/collection', query: { 'genres[]': genre } }"
              class="px-2.5 py-1 bg-shelf text-sleeve text-xs rounded-full hover:bg-groove hover:text-pressing transition-colors"
            >
              {{ genre }}
            </NuxtLink>
            <NuxtLink
              v-for="style in release.styles ?? []"
              :key="style"
              :to="{ path: '/collection', query: { 'styles[]': style } }"
              class="px-2.5 py-1 bg-groove text-label text-xs rounded-full hover:bg-jacket hover:text-pressing transition-colors"
            >
              {{ style }}
            </NuxtLink>
          </div>

          <h1 class="text-3xl font-bold text-pressing leading-tight mb-2">{{ release.title }}</h1>
          <p class="text-xl text-sleeve mb-6">{{ release.artist }}</p>

          <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div v-if="release.label">
              <dt class="text-dust text-xs font-semibold uppercase tracking-wider mb-1">Label</dt>
              <dd class="text-pressing">{{ release.label }}</dd>
            </div>
            <div v-if="release.year && release.year !== 0">
              <dt class="text-dust text-xs font-semibold uppercase tracking-wider mb-1">Year</dt>
              <dd class="text-pressing">{{ release.year }}</dd>
            </div>
            <div v-if="release.catalog_number">
              <dt class="text-dust text-xs font-semibold uppercase tracking-wider mb-1">Cat #</dt>
              <dd class="text-pressing">{{ release.catalog_number }}</dd>
            </div>
            <div v-if="formatsLabel">
              <dt class="text-dust text-xs font-semibold uppercase tracking-wider mb-1">Format</dt>
              <dd class="text-pressing">{{ formatsLabel }}</dd>
            </div>
            <div v-if="release.collection_item?.rating">
              <dt class="text-dust text-xs font-semibold uppercase tracking-wider mb-1">My Rating</dt>
              <dd class="text-yellow-400">
                {{ '★'.repeat(release.collection_item.rating) }}{{ '☆'.repeat(5 - release.collection_item.rating) }}
              </dd>
            </div>
            <div v-if="release.collection_item?.date_added">
              <dt class="text-dust text-xs font-semibold uppercase tracking-wider mb-1">Added</dt>
              <dd class="text-pressing">
                {{
                  new Date(release.collection_item.date_added).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                  })
                }}
              </dd>
            </div>
          </dl>

          <div v-if="release.notes" class="mt-6 p-4 bg-cabinet rounded-lg border border-shelf">
            <h3 class="text-xs font-semibold text-dust uppercase tracking-wider mb-2">Notes</h3>
            <p class="text-sleeve text-sm leading-relaxed">{{ release.notes }}</p>
          </div>

          <a
            v-if="release.discogs_uri"
            :href="release.discogs_uri"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2 mt-6 px-4 py-2 bg-shelf hover:bg-groove text-sleeve hover:text-pressing text-sm rounded-lg transition-colors"
          >
            View on Discogs ↗
          </a>
        </div>
      </div>

      <div v-if="release.tracklist?.length" class="mb-10">
        <h2 class="text-lg font-bold text-pressing mb-4">Tracklist</h2>
        <div class="bg-cabinet rounded-xl border border-shelf overflow-hidden">
          <div
            v-for="(track, index) in release.tracklist"
            :key="index"
            class="flex items-center gap-4 px-5 py-3 border-b border-shelf last:border-b-0 hover:bg-shelf/50 transition-colors"
            :class="track.type_ === 'heading' ? 'bg-shelf/30' : ''"
          >
            <template v-if="track.type_ !== 'heading'">
              <span class="text-jacket text-xs w-8 shrink-0 text-right">{{ track.position }}</span>
              <span class="text-pressing text-sm flex-1">{{ track.title }}</span>
              <span v-if="track.duration" class="text-dust text-xs shrink-0">{{ track.duration }}</span>
            </template>
            <template v-else>
              <span class="text-label text-xs font-semibold uppercase tracking-wider">{{ track.title }}</span>
            </template>
          </div>
        </div>
      </div>

      <div v-if="youtubeVideos.length" class="mb-10">
        <h2 class="text-lg font-bold text-pressing mb-4">Videos</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="video in youtubeVideos"
            :key="video.youtubeId"
            class="bg-cabinet rounded-xl border border-shelf overflow-hidden"
          >
            <div class="relative aspect-video">
              <iframe
                :src="`https://www.youtube-nocookie.com/embed/${video.youtubeId}`"
                :title="video.title"
                class="w-full h-full"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture;"
                allowfullscreen
              />
            </div>
            <div v-if="video.title" class="px-4 py-3">
              <p class="text-sleeve text-sm">{{ video.title }}</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>


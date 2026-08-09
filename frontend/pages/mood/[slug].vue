<script setup lang="ts">
import type { ApiEnvelope, ApiError, Suggestion } from '~/types/api'

const route = useRoute()
const { get } = useApi()

const slug = computed(() => String(route.params.slug))

const { data, error, refresh } = await useAsyncData(
  () => `mood-suggest-${slug.value}`,
  () => get<ApiEnvelope<Suggestion>>(`/moods/${slug.value}/suggest`),
  { watch: [slug] },
)

const suggestion = computed(() => data.value?.data ?? null)
const notFound = computed(() => error.value?.statusCode === 404)
const emptyMessage = computed(() => {
  if (error.value?.statusCode !== 422) return null
  return (
    (error.value.data as ApiError | undefined)?.message ??
    'Your collection is empty. Sync your Discogs collection to get suggestions.'
  )
})

const retrying = ref(false)

async function tryAgain() {
  if (retrying.value) return
  retrying.value = true
  await refresh()
  retrying.value = false
}

useHead(() => ({
  title: suggestion.value?.mood ? `${suggestion.value.mood.label} – Adam's Collection` : 'Mood',
}))
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <NuxtLink
      to="/"
      class="inline-flex items-center gap-2 text-label hover:text-pressing text-sm mb-8 transition-colors"
    >
      ← Different mood
    </NuxtLink>

    <div v-if="notFound" class="text-center py-12">
      <div class="text-6xl mb-4" aria-hidden="true">⚫</div>
      <h1 class="text-xl font-semibold text-sleeve mb-2">Mood not found</h1>
      <p class="text-label text-pretty">That mood doesn&apos;t exist. Pick one from the home page.</p>
    </div>

    <div v-else-if="emptyMessage" class="text-center py-12">
      <div class="text-6xl mb-4" aria-hidden="true">⚫</div>
      <h1 class="text-xl font-semibold text-sleeve mb-2">No suggestion available</h1>
      <p class="text-label text-pretty">{{ emptyMessage }}</p>
    </div>

    <SuggestionResult
      v-else-if="suggestion"
      :emoji="suggestion.mood?.emoji ?? '🎵'"
      :label="suggestion.mood?.label ?? ''"
      :primary="suggestion.primary"
      :backups="suggestion.backups"
      :retrying="retrying"
      @retry="tryAgain"
    />
  </div>
</template>

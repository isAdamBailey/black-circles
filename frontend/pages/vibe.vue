<script setup lang="ts">
import type { ApiEnvelope, ApiError, Suggestion } from '~/types/api'

const route = useRoute()
const { post } = useApi()

const prompt = computed(() => {
  const raw = route.query.prompt
  return typeof raw === 'string' ? raw.trim() : ''
})
const promptIsValid = computed(() => prompt.value.length >= 3)

const { data, error, refresh } = await useAsyncData(
  () => `vibe-suggest-${prompt.value}`,
  () => post<ApiEnvelope<Suggestion>>('/vibe/suggest', { prompt: prompt.value }),
  { watch: [prompt], enabled: promptIsValid },
)

const suggestion = computed(() => data.value?.data ?? null)
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
  title: suggestion.value?.mood ? `${suggestion.value.mood.label} – Adam's Collection` : 'Vibe search',
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

    <div v-if="!promptIsValid" class="text-center py-12">
      <div class="text-6xl mb-4" aria-hidden="true">⚫</div>
      <h1 class="text-xl font-semibold text-sleeve mb-2">No search to show</h1>
      <p class="text-label text-pretty">Describe a vibe from the home page to get a suggestion.</p>
    </div>

    <div v-else-if="emptyMessage" class="text-center py-12">
      <div class="text-6xl mb-4" aria-hidden="true">⚫</div>
      <h1 class="text-xl font-semibold text-sleeve mb-2">No suggestion available</h1>
      <p class="text-label text-pretty">{{ emptyMessage }}</p>
    </div>

    <SuggestionResult
      v-else-if="suggestion"
      emoji="🎵"
      :label="suggestion.mood?.label ?? prompt"
      :primary="suggestion.primary"
      :backups="suggestion.backups"
      :retrying="retrying"
      @retry="tryAgain"
    />
  </div>
</template>

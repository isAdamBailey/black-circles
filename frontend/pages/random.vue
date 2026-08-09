<script setup lang="ts">
import type { ApiEnvelope, ApiError, Release } from '~/types/api'

const { get } = useApi()

const { data, error } = await useAsyncData('random-release', () => get<ApiEnvelope<Release>>('/collection/random'))

watchEffect(() => {
  if (data.value?.data) {
    navigateTo(`/collection/${data.value.data.discogs_id}`, { replace: true })
  }
})

const emptyMessage = computed(() => {
  if (error.value?.statusCode !== 404) return null
  return (
    (error.value.data as ApiError | undefined)?.message ??
    'Your collection is empty. Sync your Discogs collection to get suggestions.'
  )
})

useHead({ title: 'Random release' })
</script>

<template>
  <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12 text-center">
    <div v-if="emptyMessage" class="py-12">
      <div class="text-6xl mb-4" aria-hidden="true">⚫</div>
      <h1 class="text-xl font-semibold text-sleeve mb-2">No release to show</h1>
      <p class="text-label text-pretty mb-6">{{ emptyMessage }}</p>
      <NuxtLink to="/" class="inline-flex items-center gap-2 text-label hover:text-pressing text-sm">
        ← Back home
      </NuxtLink>
    </div>

    <div v-else-if="error" class="py-12" role="alert">
      <h1 class="text-xl font-semibold text-sleeve mb-2">Couldn&apos;t load a release</h1>
      <p class="text-label text-pretty">The API didn&apos;t respond. Check that the backend is running, then try again.</p>
    </div>

    <p v-else class="text-label py-12">Finding a random release…</p>
  </div>
</template>

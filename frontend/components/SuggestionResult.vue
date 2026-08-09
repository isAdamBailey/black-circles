<script setup lang="ts">
import type { ReleaseSummary } from '~/types/api'

defineProps<{
  emoji: string
  label: string
  primary: ReleaseSummary
  backups: ReleaseSummary[]
  retrying: boolean
}>()

defineEmits<{ retry: [] }>()
</script>

<template>
  <div>
    <div class="text-center mb-6">
      <span class="text-3xl">{{ emoji }}</span>
      <h1 class="text-2xl font-bold text-pressing mt-2">{{ label }}</h1>
    </div>

    <div class="flex flex-col md:flex-row gap-8 mb-10">
      <NuxtLink :to="`/collection/${primary.discogs_id}`" class="group block md:flex-shrink-0 md:w-2/5">
        <div
          class="aspect-square bg-shelf rounded-xl overflow-hidden mb-4 group-hover:scale-[1.02] transition-transform"
        >
          <img
            v-if="primary.cover_image"
            :src="primary.cover_image"
            :alt="primary.title"
            class="w-full h-full object-cover"
          >
          <div v-else class="w-full h-full flex items-center justify-center text-8xl text-jacket">⚫</div>
        </div>
        <h2 class="text-xl font-bold text-pressing truncate group-hover:underline">{{ primary.title }}</h2>
        <p class="text-label truncate">{{ primary.artist }}</p>
        <div class="flex flex-wrap gap-1.5 mt-2">
          <span
            v-for="g in primary.genres"
            :key="g"
            class="px-2 py-0.5 bg-shelf text-label text-xs rounded-full"
          >{{ g }}</span>
          <span
            v-for="s in primary.styles"
            :key="s"
            class="px-2 py-0.5 bg-groove text-dust text-xs rounded-full"
          >{{ s }}</span>
        </div>
      </NuxtLink>

      <div class="flex-1 flex flex-col justify-center">
        <p class="text-label mb-6">Picked from Adam&apos;s collection. Open details or try again.</p>
        <button
          type="button"
          class="self-start px-5 py-2.5 bg-shelf hover:bg-groove border border-jacket rounded-lg text-pressing text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-shelf"
          :disabled="retrying"
          @click="$emit('retry')"
        >
          {{ retrying ? 'Finding...' : 'Try again' }}
        </button>
      </div>
    </div>

    <div v-if="backups.length" class="border-t border-shelf pt-8">
      <h3 class="text-sm font-semibold text-dust uppercase tracking-wider mb-4">Also in Adam&apos;s collection</h3>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <NuxtLink v-for="r in backups" :key="r.discogs_id" :to="`/collection/${r.discogs_id}`" class="group block">
          <div
            class="relative aspect-square bg-shelf rounded-lg overflow-hidden mb-2 group-hover:scale-105 transition-transform"
          >
            <img
              v-if="r.cover_image || r.thumb"
              :src="r.cover_image || r.thumb || undefined"
              :alt="r.title"
              class="w-full h-full object-cover"
            >
            <div v-else class="w-full h-full flex items-center justify-center text-4xl text-jacket">⚫</div>
          </div>
          <p class="text-pressing text-sm font-medium truncate">{{ r.title }}</p>
          <p class="text-label text-xs truncate">{{ r.artist }}</p>
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

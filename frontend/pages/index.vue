<script setup lang="ts">
import type { ApiEnvelope, HomeData } from '~/types/api'

const { get } = useApi()

// `lazy` so the page shell renders immediately and the mood grid can show its
// own loading state — in SPA mode a blocking fetch would leave a blank screen.
const {
  data: home,
  status,
  error,
  refresh,
} = useAsyncData('home', () => get<ApiEnvelope<HomeData>>('/home'), { lazy: true })

const moods = computed(() => home.value?.data.moods ?? [])
const username = computed(() => home.value?.data.username ?? '')
const insight = computed(() => home.value?.data.insight ?? '')

const prompt = ref('')
const processing = ref(false)
const moodSectionOpen = ref(true)
const vibeError = ref('')

const promptIsValid = computed(() => prompt.value.trim().length >= 3)

async function submitVibe() {
  if (!promptIsValid.value) return

  processing.value = true
  vibeError.value = ''

  try {
    await navigateTo({ path: '/vibe', query: { prompt: prompt.value.trim() } })
  } catch {
    vibeError.value = 'Something went wrong finding a match — try again.'
  } finally {
    processing.value = false
  }
}

useHead({ title: 'Discover' })
</script>

<template>
  <div class="min-h-[calc(100vh-4rem)] flex flex-col">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-1 flex flex-col justify-center w-full">
      <h1
        class="text-4xl sm:text-5xl font-bold tracking-[-0.02em] text-balance text-pressing text-center mb-3"
      >
        Adam&apos;s Black Circles
      </h1>
      <p class="text-label leading-relaxed text-center mb-10 max-w-3xl mx-auto">
        Search and pick records based on mood. Browse and sort the full collection, or jump into random
        releases.
      </p>

      <div class="mb-10">
        <div class="flex items-center justify-between max-w-3xl mx-auto mb-6">
          <h2 class="text-2xl font-bold tracking-[-0.01em] text-pressing">Pick a mood</h2>
          <button
            v-if="username"
            type="button"
            class="text-sm text-label hover:text-pressing transition-colors"
            :aria-expanded="moodSectionOpen"
            aria-controls="mood-section-panel"
            @click="moodSectionOpen = !moodSectionOpen"
          >
            {{ moodSectionOpen ? 'Hide' : 'Show' }}
          </button>
        </div>

        <div v-if="status === 'pending'" class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
          <div
            v-for="n in 4"
            :key="n"
            class="aspect-square sm:aspect-[4/3] bg-cabinet border-2 border-shelf rounded-2xl motion-safe:animate-pulse"
          />
        </div>

        <div v-else-if="error" class="text-center py-12" role="alert">
          <h3 class="text-xl font-semibold text-sleeve mb-2">Couldn&apos;t load the collection</h3>
          <p class="text-label text-pretty mb-4">
            The API didn&apos;t respond. Check that the backend is running, then try again.
          </p>
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-md bg-shelf hover:bg-groove border border-jacket px-5 py-2.5 text-sm font-semibold text-pressing transition-colors"
            @click="refresh()"
          >
            Try again
          </button>
        </div>

        <div v-else-if="moodSectionOpen" id="mood-section-panel">
          <form
            v-if="username"
            class="mb-2 flex flex-col sm:flex-row gap-3 max-w-3xl mx-auto w-full"
            @submit.prevent="submitVibe"
          >
            <input
              v-model="prompt"
              type="text"
              placeholder="e.g. dark moody post-punk for a late night drive"
              class="flex-1 px-4 py-3 bg-cabinet border border-groove rounded-lg text-pressing placeholder-dust focus:border-oxblood-bright focus:ring-1 focus:ring-oxblood-bright"
              :disabled="processing"
            >
            <button
              type="submit"
              :disabled="processing || !promptIsValid"
              class="inline-flex items-center justify-center rounded-lg bg-oxblood px-5 py-3 text-sm font-semibold text-pressing transition-colors hover:bg-oxblood-bright focus:outline-none focus:ring-2 focus:ring-oxblood-bright focus:ring-offset-2 focus:ring-offset-void disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-oxblood"
            >
              <svg
                v-if="processing"
                class="motion-safe:animate-spin -ml-0.5 mr-2 h-4 w-4"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                />
              </svg>
              {{ processing ? 'Finding...' : 'Find it' }}
            </button>
          </form>
          <p v-if="vibeError" role="alert" class="text-center text-signal-error-text text-base mb-6">
            {{ vibeError }}
          </p>
          <p v-else-if="username" class="text-center text-label text-base mb-6">
            Uses Adam&apos;s collection — results may vary
          </p>

          <div v-if="!username" class="text-center py-12">
            <div class="text-6xl mb-4" aria-hidden="true">⚫</div>
            <h3 class="text-xl font-semibold text-sleeve mb-2">No collection synced yet</h3>
            <p class="text-label text-pretty mb-2">
              Set
              <code class="text-label bg-shelf px-1.5 py-0.5 rounded text-sm">DISCOGS_USERNAME</code>
              in .env and run
              <code class="text-label bg-shelf px-1.5 py-0.5 rounded text-sm">sail artisan discogs:sync</code>
              to get started.
            </p>
          </div>

          <div
            v-else
            class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6"
            :class="{ 'pointer-events-none opacity-60 cursor-not-allowed': processing }"
          >
            <NuxtLink
              v-for="mood in moods"
              :key="mood.slug"
              :to="`/mood/${mood.slug}`"
              class="group flex flex-col items-center justify-center aspect-square sm:aspect-[4/3] bg-cabinet border-2 border-shelf rounded-2xl p-6 text-center transition-all hover:border-jacket hover:bg-shelf/80"
            >
              <span class="text-4xl sm:text-5xl mb-3 block group-hover:scale-110 transition-transform">{{
                mood.emoji
              }}</span>
              <span class="text-lg font-semibold text-pressing">{{ mood.label }}</span>
            </NuxtLink>
          </div>
        </div>
      </div>

      <div class="mb-10 flex flex-col sm:flex-row items-center justify-center gap-3">
        <NuxtLink
          v-if="username"
          to="/random"
          class="inline-flex items-center justify-center gap-2 min-w-[200px] px-5 py-2.5 bg-oxblood hover:bg-oxblood-bright border border-transparent rounded-lg text-pressing text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-oxblood-bright focus:ring-offset-2 focus:ring-offset-void"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          Random release
        </NuxtLink>
        <NuxtLink
          to="/collection"
          class="inline-flex items-center justify-center min-w-[200px] px-5 py-2.5 bg-oxblood hover:bg-oxblood-bright border border-transparent rounded-lg text-pressing text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-oxblood-bright focus:ring-offset-2 focus:ring-offset-void"
        >
          Browse collection
        </NuxtLink>
      </div>

      <div v-if="username && insight" class="mt-8 rounded-xl bg-cabinet border border-shelf p-6">
        <h2 class="text-xl font-bold tracking-[-0.01em] text-pressing mb-3">Adam&apos;s music personality</h2>
        <p class="text-sleeve leading-relaxed text-pretty whitespace-pre-line">{{ insight }}</p>
        <p class="text-xs text-dust italic mt-4">A quick read on the collection, AI-generated.</p>
      </div>
    </div>
  </div>
</template>

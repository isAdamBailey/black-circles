<script setup lang="ts">
import type { HomeData } from '~/types/api'

const { get } = useApi()

const { data: home, error } = await useAsyncData('home-scaffold-check', () =>
  get<HomeData>('/home'),
)
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-6 text-center font-sans">
    <div>
      <h1 class="text-2xl font-semibold">Black Circles — Nuxt scaffold</h1>
      <p class="mt-2 text-label">
        Pages ship in Phase 4. This confirms the API client reaches
        <code class="text-sleeve">/api/v1/home</code>.
      </p>
      <p v-if="home" class="mt-4 text-sleeve">
        Loaded {{ home.moods.length }} moods from the API.
      </p>
      <p v-else-if="error" class="mt-4 text-signal-error-text">
        Could not reach the API: {{ error.message }}
      </p>
    </div>
  </div>
</template>

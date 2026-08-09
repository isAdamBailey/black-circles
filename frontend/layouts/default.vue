<script setup lang="ts">
const route = useRoute()

const collectionActive = computed(() => route.path.startsWith('/collection'))
</script>

<template>
  <div class="min-h-screen bg-void text-sleeve flex flex-col">
    <!-- Navigation -->
    <nav class="bg-cabinet border-b border-shelf sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center gap-8">
            <NuxtLink to="/" class="flex items-center gap-2">
              <VinylRecordLogo class="h-8 w-8" />
              <span class="text-xl font-bold tracking-tight text-pressing">Black Circles</span>
            </NuxtLink>
            <div class="hidden sm:flex items-center gap-6">
              <NuxtLink
                to="/collection"
                class="text-sm font-medium transition-colors"
                :class="collectionActive ? 'text-pressing' : 'text-label hover:text-pressing'"
              >
                Collection
              </NuxtLink>
            </div>
          </div>
          <!-- Mobile nav -->
          <div class="sm:hidden flex gap-4">
            <NuxtLink to="/collection" class="text-label hover:text-pressing text-sm">Collection</NuxtLink>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <main class="flex-1">
      <slot />
    </main>

    <footer class="border-t border-shelf py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a
          href="https://adambailey.io"
          target="_blank"
          rel="noopener noreferrer"
          class="text-sm text-dust hover:text-sleeve transition-colors"
        >
          © 2026 Adam Bailey
        </a>
      </div>
    </footer>
  </div>
</template>

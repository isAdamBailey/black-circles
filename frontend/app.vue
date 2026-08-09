<script setup lang="ts">
const appName = 'Black Circles'
const description =
  "Discover music from Adam's vinyl collection. Pick a mood and get suggestions from Adam's Discogs collection."

useHead({
  htmlAttrs: { lang: 'en' },
  bodyAttrs: { class: 'font-sans antialiased' },
  title: appName,
  titleTemplate: (title) => (title && title !== appName ? `${title} - ${appName}` : appName),
  meta: [
    { name: 'description', content: description },
    { property: 'og:type', content: 'website' },
    { property: 'og:title', content: appName },
    { property: 'og:description', content: description },
    { name: 'twitter:card', content: 'summary_large_image' },
    { name: 'twitter:title', content: appName },
    { name: 'twitter:description', content: description },
  ],
  link: [
    { rel: 'icon', type: 'image/svg+xml', href: '/images/logo.svg' },
    { rel: 'preconnect', href: 'https://fonts.bunny.net' },
    {
      rel: 'stylesheet',
      href: 'https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap',
    },
  ],
})
</script>

<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>

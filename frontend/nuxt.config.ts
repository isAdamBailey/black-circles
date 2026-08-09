import tailwindcss from '@tailwindcss/vite'

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  // Client-only rendering — every page already fetches its own data via
  // useAsyncData with lazy/skeleton states (see Phase 4 PRs), so no SSR
  // rendering pass is needed. `nuxt build` still produces a Node server
  // (.output/server/index.mjs) that serves this SPA shell + static assets
  // in production — see ../DEPLOY.md for how it's run behind nginx.
  ssr: false,

  typescript: {
    strict: true,
    typeCheck: false,
  },

  css: ['~/assets/css/main.css'],

  vite: {
    plugins: [tailwindcss()],
  },

  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost/api/v1',
    },
  },

  nitro: {
    devProxy: {
      '/api': {
        target: 'http://localhost/api',
        changeOrigin: true,
      },
    },
  },
})

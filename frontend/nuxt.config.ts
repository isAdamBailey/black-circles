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

  app: {
    head: {
      meta: [{ name: 'theme-color', content: '#030712' }],
      link: [{ rel: 'manifest', href: '/manifest.webmanifest' }],
    },
  },

  modules: ['nuxt-gtag', '@vite-pwa/nuxt'],

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

  // GA4 tag ID — optional; the module no-ops with no id set. Reuses
  // VITE_GOOGLE_TAG_ID, the env var already set in Forge/`.env` from the old
  // Vite/vue-gtag setup, rather than introducing a new name. Read directly
  // here (not via Nuxt's NUXT_PUBLIC_* runtime-config env convention), which
  // means it's baked in at `npm run build` time, not re-read at server
  // start — see DEPLOY.md for the Deploy Script's `source .env` step this
  // needs. Enhanced Measurement's "page changes based on browser history
  // events" covers SPA route-change tracking automatically — no manual
  // page_view wiring like the old setup needed.
  gtag: {
    id: process.env.VITE_GOOGLE_TAG_ID || '',
  },

  nitro: {
    devProxy: {
      '/api': {
        target: 'http://localhost/api',
        changeOrigin: true,
      },
    },
  },

  pwa: {
    registerType: 'autoUpdate',
    includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'images/logo.svg', 'images/favicon-32x32.png'],
    manifest: {
      name: 'Black Circles',
      short_name: 'Black Circles',
      description:
        "Discover music from Adam's vinyl collection. Pick a mood and get suggestions from Adam's Discogs collection.",
      theme_color: '#030712',
      background_color: '#030712',
      display: 'standalone',
      start_url: '/',
      lang: 'en',
      categories: ['music', 'entertainment'],
      icons: [
        {
          src: 'pwa-192x192.png',
          sizes: '192x192',
          type: 'image/png',
          purpose: 'any',
        },
        {
          src: 'pwa-512x512.png',
          sizes: '512x512',
          type: 'image/png',
          purpose: 'any',
        },
        {
          src: 'pwa-maskable-512x512.png',
          sizes: '512x512',
          type: 'image/png',
          purpose: 'maskable',
        },
      ],
    },
    workbox: {
      globPatterns: ['**/*.{js,css,png,svg,ico,txt,woff2,webmanifest}'],
      runtimeCaching: [
        {
          urlPattern: /\/api\//,
          handler: 'NetworkOnly',
        },
        {
          urlPattern: /^https:\/\/fonts\.bunny\.net\/.*/i,
          handler: 'CacheFirst',
          options: {
            cacheName: 'bunny-fonts',
            expiration: {
              maxEntries: 16,
              maxAgeSeconds: 60 * 60 * 24 * 365,
            },
            cacheableResponse: { statuses: [0, 200] },
          },
        },
        {
          urlPattern: /^https:\/\/i\.discogs\.com\/.*/i,
          handler: 'CacheFirst',
          options: {
            cacheName: 'discogs-images',
            expiration: {
              maxEntries: 200,
              maxAgeSeconds: 60 * 60 * 24 * 30,
            },
            cacheableResponse: { statuses: [0, 200] },
          },
        },
      ],
    },
  },
})

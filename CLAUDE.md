# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this app is

Black Circles is a Laravel API + Nuxt 4 (TypeScript SPA) app to explore a Discogs vinyl record collection with AI features — mood-based browsing, plain-English "vibe" search, and a personality profile generated from listening history. It uses Meilisearch (via Laravel Scout) for full-text search and Claude (Anthropic API) for AI features.

Laravel is a JSON API only (`routes/api.php`, prefixed `/api/v1`) — it has no pages of its own. [`frontend/`](frontend/) (Nuxt 4) is the only UI. This split happened in the migration tracked by issue #5 (Inertia → Nuxt); see [`DEPLOY.md`](DEPLOY.md) for the production Forge topology.

## Commands

### Local development (with Docker / Sail) — backend

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

### Local development (without Docker) — backend

```bash
composer dev        # starts PHP server, queue worker, and pail log viewer concurrently (backend only)
```

### Local development — frontend

```bash
cd frontend
npm install
npm run dev          # Nuxt dev server on :3000, proxies /api to Sail (:80)
```

Navigate to http://localhost:3000/.

### PHP

```bash
composer test                    # clears config cache then runs Pest suite
php artisan test                 # run all PHP tests
php artisan test --filter=Name   # run a single test class or method
./vendor/bin/pint                # format PHP (Laravel Pint)
```

### Frontend (`frontend/`)

```bash
npm run build         # production build (Node server output, see DEPLOY.md)
npm run generate      # static prerender (not used in production; build is)
npm run typecheck     # vue-tsc
npm test              # Vitest
```

### Root (Playwright e2e + lint for `e2e/` and root config files)

```bash
npm run test:e2e       # Playwright against the Nuxt build (see e2e/)
npm run lint           # ESLint
npm run lint:fix       # ESLint with auto-fix
npm run format         # Prettier write
npm run format:check   # Prettier check
```

### Data / AI

```bash
php artisan discogs:sync [username]   # pull collection from Discogs API
php artisan personality:generate      # regenerate AI personality insight
```

## Architecture

### Backend (Laravel 13, PHP 8.5)

- `app/Http/Controllers/Api/V1/` — thin controllers; most logic is in services
  - `HomeController` — moods list + personality insight
  - `MoodController` — mood-based suggestions
  - `VibeController` — plain-English vibe search (synchronous)
  - `CollectionController` — collection grid, search, detail, random
- `app/Http/Resources/` — API Resources shaping the JSON responses (`MoodResource`, `ReleaseResource`, `ReleaseSummaryResource`, `SuggestionResource`, `HomeResource`, `CollectionIndexResource`)
- `app/Services/` — all business logic lives here
  - `DiscogsService` — syncs releases and collection items from Discogs API
  - `ClaudeService` — thin wrapper around the Anthropic PHP SDK (model, effort, timeouts, refusal fallback, error handling); never throws to callers
  - `VibeTagSelector` — asks Claude to pick genres/styles for a free-text vibe from the collection's own tag lists (validated, cached per prompt for a day), falling back to `MoodKeywordMatcher`
  - `MoodKeywordMatcher` — keyword/dictionary fallback for vibe tags when Claude is unavailable
  - `VibeSuggestionService` — builds mood/vibe suggestions (matching + ranking)
  - `CollectionQueryService` — shared filter/sort logic for the collection endpoints
  - `ReleaseSuggestionService` — mood-to-release matching
  - `PersonalityInsightService` — generates AI personality blurb from top genres/styles
- `app/Models/` — `DiscogsCollectionItem`, `DiscogsRelease`, `Genre`, `Mood`, `Style`, `MoodGenre`, `MoodStyle`, `MoodExcludeStyle`, `Setting`, `User`

Vibe search (`POST /api/v1/vibe/suggest`) and mood suggestions are synchronous — no queue/polling involved. The queue worker is only needed for `discogs:sync`/`personality:generate` (dispatched from `routes/console.php`'s weekly schedule).

### Frontend (Nuxt 4, TypeScript, SPA mode)

- `frontend/pages/` — one file per route (file-based routing)
- `frontend/components/` — reusable Vue components
- `frontend/layouts/default.vue` — nav + footer
- `frontend/composables/useApi.ts` — typed `$fetch` wrapper around `/api/v1`
- `frontend/types/api.ts` — types mirroring the backend's API Resources
- `ssr: false` — every page fetches its own data client-side with `useAsyncData(..., { lazy: true })` plus a skeleton/error state; there is no server-rendered pass, only a Node process serving the built SPA shell + static assets in production (see DEPLOY.md)
- Tailwind CSS v4 via `@tailwindcss/vite`

### AI (Claude via the Anthropic PHP SDK)

Both features use `claude-opus-5-5` at `low` effort through `ClaudeService`. `ClaudeService` builds the SDK client per call with a Guzzle transport that enforces the timeout (the SDK itself doesn't). Its Guzzle `HandlerStack` comes from a contextual binding in `AppServiceProvider`; tests swap it for a mock via `TestCase::fakeClaude()` and never call the real API.

| Feature | How Claude is used |
|---|---|
| Personality insight | `PersonalityInsightService` sends the top styles/genres and saves the paragraph (`personality:generate`) |
| Vibe search | In `VibeSuggestionService`'s non-preset branch, `VibeTagSelector` asks for genres/styles/exclusions as structured JSON (9 s timeout, no retries). Results are intersected with the collection's tags, then go through `fetchMatchingReleases` → merge with the Scout pool → `ReleaseRanker`. On no key, error, timeout or no usable tags it falls back to `MoodKeywordMatcher`; after an outage-type failure (connection, timeout, 5xx, 429) `ClaudeService::isAvailable()` reports false for 60 s, so vibe search skips Claude. `POST /vibe/suggest` is rate-limited to 20/min per IP (`throttle:vibe`). Mood presets with tags never call Claude |

Set `ANTHROPIC_API_KEY` to enable these features. Without it, personality generation is skipped and vibe search is keyword-only.

### Infrastructure

- MySQL 8.4 for primary data
- Meilisearch for full-text search (Laravel Scout); index populated during `discogs:sync`
- Queue driver: database (default); a queue worker must be running for `discogs:sync`/`personality:generate` to complete
- Deployed via Laravel Forge — Laravel (PHP-FPM) + a Nuxt Node process behind nginx, single origin; weekly sync cron runs Sunday midnight PST. See [`DEPLOY.md`](DEPLOY.md) for the full Forge setup.

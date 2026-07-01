# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this app is

Black Circles is a Laravel + Vue 3 (Inertia.js) app to explore a Discogs vinyl record collection with AI features — mood-based browsing, plain-English "vibe" search, and a personality profile generated from listening history. It uses Meilisearch (via Laravel Scout) for full-text search and Hugging Face-hosted models for AI inference.

## Commands

### Local development (without Docker)

```bash
composer dev        # starts PHP server, queue worker, pail log viewer, and Vite concurrently
```

### Local development (with Docker / Sail)

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

### PHP

```bash
composer test                    # clears config cache then runs Pest suite
php artisan test                 # run all PHP tests
php artisan test --filter=Name   # run a single test class or method
./vendor/bin/pint                # format PHP (Laravel Pint)
```

### JavaScript

```bash
npm run dev           # Vite dev server
npm run build         # production build
npm test              # Vitest (JS unit tests in resources/js/**/*.{test,spec}.js)
npm run lint          # ESLint
npm run lint:fix      # ESLint with auto-fix
npm run format        # Prettier write
npm run format:check  # Prettier check
```

### Data / AI

```bash
php artisan discogs:sync [username]   # pull collection from Discogs API
php artisan personality:generate      # regenerate AI personality insight
```

## Architecture

### Backend (Laravel 13, PHP 8.3)

- `app/Http/Controllers/` — thin controllers; most logic is in services
  - `MoodController` — home page + mood-based suggestions
  - `VibeController` — dispatches and polls the async vibe-search job
  - `CollectionController` — collection grid, search, detail, random
- `app/Services/` — all business logic lives here
  - `DiscogsService` — syncs releases and collection items from Discogs API
  - `HuggingFaceService` — wraps HF inference API calls
  - `VibeSuggestionService` / `AiSuggestionDispatchService` — orchestrate the vibe search pipeline
  - `ReleaseSuggestionService` — mood-to-release matching
  - `PersonalityInsightService` — generates AI personality blurb from top genres/styles
- `app/Jobs/ProcessVibeSuggestion.php` — queued job for vibe search; results stored in cache under a token
- `app/Models/` — `DiscogsCollectionItem`, `DiscogsRelease`, `Genre`, `Mood`, `Style`, `MoodGenre`, `MoodStyle`, `MoodExcludeStyle`, `Setting`, `User`

### Vibe search async flow

`POST /vibe` → `VibeController::suggest` dispatches `ProcessVibeSuggestion` job and returns a token → frontend polls `GET /vibe/poll/{token}` → when ready, redirects to `GET /vibe/result/{token}`. Cache (database driver by default) is the shared state between web and queue containers.

### Frontend (Vue 3 + Inertia.js)

- `resources/js/app.js` — Inertia bootstrap
- `resources/js/Pages/` — one file per route (Inertia pages)
- `resources/js/Components/` — reusable Vue components
- `resources/js/Layouts/` — page layouts
- Path alias `@` resolves to `resources/js/`
- Tailwind CSS v3 for styling; `@tailwindcss/forms` plugin included

### AI models (Hugging Face Inference API)

| Feature | Model |
|---|---|
| Vibe / mood search | `MoritzLaurer/deberta-v3-base-zeroshot-v2.0` (zero-shot classification) |
| Personality insight | `Qwen/Qwen2.5-1.5B-Instruct` (instruction-tuned LLM) |

Set `HUGGINGFACE_API_TOKEN` to enable these features.

### Infrastructure

- MySQL 8.4 for primary data
- Meilisearch for full-text search (Laravel Scout); index populated during `discogs:sync`
- Queue driver: database (default); a queue worker must be running for AI jobs to complete
- Deployed via Laravel Forge; weekly sync cron runs Sunday midnight PST

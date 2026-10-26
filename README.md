# Black Circles

[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2F6a1b8989-96fb-459d-9be9-3a08b70fa53c&style=plastic)](https://forge.laravel.com/adam-f6w/stunning-fern-s7f/3451761)

A Laravel API + Nuxt 4 application to display, explore, and understand your [Discogs](https://www.discogs.com) vinyl record collection — powered by AI.

## Features

- 🎵 **Sync your Discogs collection** by username — no auth required for public collections
- 🎶 **Mood search** — describe the music you want in plain English and get matched releases from your collection
- 🎭 **AI personality insight** — your top styles and genres are analysed to generate a personalised personality profile based on your taste
- 💿 **Album grid** with cover art, filters, and Discogs marketplace pricing
- 🎬 **Detail pages** with tracklist and YouTube preview embeds

## Tech Stack

Laravel 13 (PHP 8.5) JSON API, Nuxt 4 (TypeScript, Tailwind) SPA in [`frontend/`](frontend/), MySQL 8.4, Meilisearch (Scout), [Discogs API](https://www.discogs.com/developers/).

## AI Models

Both AI features use Claude (`claude-opus-5-5`) through the official Anthropic PHP SDK. Mood presets are matched locally against their curated genres and styles, with no AI involved.

| Feature | How it works |
|---|---|
| Vibe search | Claude picks genres and styles from the collection's own tag lists that fit a plain-English prompt. Without a key, or if the call fails, it falls back to local keyword matching |
| Personality insight | Claude writes a short paragraph about the collection from its top styles and genres |

## Running locally

You need [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or another Docker engine). [Composer](https://getcomposer.org/) on the host is enough to install PHP dependencies; Sail runs the app inside containers.

### 1. Install dependencies and env

```bash
composer install
cp .env.example .env
```

Edit `.env` with at least the variables in the table below. Defaults already point Sail at the bundled MySQL and Meilisearch (`DB_HOST=mysql`, `MEILISEARCH_HOST=http://meilisearch:7700`).

### 2. Start Sail

Use the Sail binary (or a [`sail` shell alias](https://laravel.com/docs/sail#configuring-a-shell-alias)):

```bash
./vendor/bin/sail up -d
```

This starts the app (`laravel.test`), MySQL, Meilisearch, and a **`queue`** container that runs `php artisan queue:work` (currently idle — no background jobs are queued, but the worker is kept running for future use).

### 3. App setup

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

Then, in a separate terminal, run the frontend (see [`frontend/README.md`](frontend/README.md)):

```bash
cd frontend
npm install
npm run dev
```

Open **http://localhost:3000** (the Nuxt dev server proxies `/api` to Sail on `:80`).

### 4. Load your collection

Sync from Discogs (username can come from `.env` or the argument):

```bash
./vendor/bin/sail artisan discogs:sync your_discogs_username
```

Until you sync, grids and AI suggestions have nothing to match against.

### Environment variables

| Variable | Required | Notes |
|---|---|---|
| `DISCOGS_USERNAME` | For automated / scheduled sync | Your public Discogs username; you can also pass username to `discogs:sync` |
| `DISCOGS_TOKEN` | No | Improves Discogs rate limits — [discogs.com/settings/developers](https://www.discogs.com/settings/developers) |
| `ANTHROPIC_API_KEY` | No | **Personality** insight and Claude-assisted **vibe** search — [platform.claude.com/settings/keys](https://platform.claude.com/settings/keys). Without it, vibe search falls back to keyword matching |

### If you are not using this repo’s `docker-compose.yml`

No queue worker is required — mood/vibe search run synchronously, and there are currently no queued background jobs.

## Syncing

Sync runs weekly (Sunday midnight PST). Manually: `sail artisan discogs:sync` or `sail artisan discogs:sync username`.

The personality insight is generated automatically after each sync. To generate it manually:

```bash
sail artisan personality:generate
```

## Deployment (Forge)

[Laravel Forge](https://forge.laravel.com) — MySQL via Forge, Meilisearch on server, Nuxt run as a Forge Daemon behind nginx. See [`DEPLOY.md`](DEPLOY.md) for the full setup.

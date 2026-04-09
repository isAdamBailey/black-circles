# Black Circles

[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2Fe02bdf85-96d4-4d8e-8982-507d89b24bec&style=flat)](https://forge.laravel.com/adam-f6w/adambaileyio/3060937)

A Laravel + Vue (Inertia.js) application to display, explore, and understand your [Discogs](https://www.discogs.com) vinyl record collection — powered by AI.

## Features

- 🎵 **Sync your Discogs collection** by username — no auth required for public collections
- 🤖 **AI mood search** — describe the music you want in plain English and get matched releases from your collection
- 🎭 **AI personality insight** — your top styles and genres are analysed to generate a personalised personality profile based on your taste
- 💿 **Album grid** with cover art, filters, and Discogs marketplace pricing
- 🎬 **Detail pages** with tracklist and YouTube preview embeds

## Tech Stack

Laravel 12 (PHP 8.3), Vue 3 + Inertia.js, Tailwind, MySQL 8.4, Meilisearch (Scout), [Discogs API](https://www.discogs.com/developers/).

## AI Models

| Feature | Model |
|---|---|
| Mood search | `MoritzLaurer/deberta-v3-base-zeroshot-v2.0` — zero-shot text classification |
| Personality insight | `Qwen/Qwen2.5-1.5B-Instruct` — instruction-tuned LLM |

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

This starts the app (`laravel.test`), MySQL, Meilisearch, and a **`queue`** container that runs `php artisan queue:work`. Natural-language vibe search and AI mood suggestions are queued; without a worker they will not finish.

### 3. App setup

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Open **http://localhost** unless you changed `APP_PORT` / port mappings in `.env`.

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
| `HUGGINGFACE_API_TOKEN` | No | **Vibe** search, AI **mood** picks, and **personality** insight — [huggingface.co/settings/tokens](https://huggingface.co/settings/tokens) (Read scope is enough) |
| `VITE_GOOGLE_TAG_ID` | No | Google Analytics tag ID |

Optional: `VIBE_POLL_TIMEOUT_SECONDS` (min 30 seconds when set) controls how long the wait page will poll before treating a stuck job as timed out.

### If you are not using this repo’s `docker-compose.yml`

Run a queue worker in a second terminal so AI suggestions can complete:

```bash
./vendor/bin/sail artisan queue:work
```

Use a **shared** cache store (this project defaults to `CACHE_STORE=database`) for both the web container and the worker so vibe wait/poll state is visible everywhere.

## Syncing

Sync runs weekly (Sunday midnight PST). Manually: `sail artisan discogs:sync` or `sail artisan discogs:sync username`.

The personality insight is generated automatically after each sync. To generate it manually:

```bash
sail artisan personality:generate
```

## Deployment (Forge)

[Laravel Forge](https://forge.laravel.com) — MySQL via Forge, Meilisearch on server. Set env vars in Forge Environment editor.

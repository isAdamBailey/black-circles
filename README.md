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

## Setup

Requires [Docker Desktop](https://www.docker.com/products/docker-desktop/).

```bash
composer install
cp .env.example .env
sail up -d
sail artisan key:generate
sail artisan migrate
sail npm install
sail npm run dev
```

Add to `.env`:

| Variable | Required | Notes |
|---|---|---|
| `DISCOGS_USERNAME` | Yes | Your public Discogs username |
| `DISCOGS_TOKEN` | No | Improves rate limits — get at [discogs.com/settings/developers](https://www.discogs.com/settings/developers) |
| `HUGGINGFACE_TOKEN` | No | Enables AI mood search and personality insight — get at [huggingface.co/settings/tokens](https://huggingface.co/settings/tokens) |
| `VITE_GOOGLE_TAG_ID` | No | Google Analytics tag ID |

## Syncing

Sync runs weekly (Sunday midnight PST). Manually: `sail artisan discogs:sync` or `sail artisan discogs:sync username`.

The personality insight is generated automatically after each sync. To generate it manually:

```bash
sail artisan personality:generate
```

## Deployment (Forge)

[Laravel Forge](https://forge.laravel.com) — MySQL via Forge, Meilisearch on server. Set env vars in Forge Environment editor.

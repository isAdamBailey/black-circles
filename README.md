# Black Circles

[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2Fe02bdf85-96d4-4d8e-8982-507d89b24bec&style=flat)](https://forge.laravel.com/adam-f6w/adambaileyio/3060937)

A Laravel + Vue (Inertia.js) application to display and organize your [Discogs](https://www.discogs.com) vinyl record collection.

## Features

- 🎵 **Sync your Discogs collection** by username — no auth required for public collections
- 🎲 **Mood-based discovery** — pick a mood and get suggestions from your collection
- 💿 **Album grid** with cover art, filters, and Discogs marketplace pricing
- 🎬 **Detail pages** with tracklist and YouTube preview embeds

## Tech Stack

Laravel 12 (PHP 8.3), Vue 3 + Inertia.js, Tailwind, MySQL 8.4, Meilisearch (Scout), [Discogs API](https://www.discogs.com/developers/).

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

Add to `.env`: `DISCOGS_USERNAME` (required), `DISCOGS_TOKEN` (optional, improves rate limits — get at https://www.discogs.com/settings/developers), `VITE_GOOGLE_TAG_ID` (optional).

## Syncing

Sync runs weekly (Sunday midnight PST). Manually: `sail artisan discogs:sync` or `sail artisan discogs:sync username`.

## Deployment (Forge)

[Laravel Forge](https://forge.laravel.com) — MySQL via Forge, Meilisearch on server. Set env vars in Forge Environment editor.

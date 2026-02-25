# Black Circles

A Laravel + Vue (Inertia.js) application to display and organize your [Discogs](https://www.discogs.com) vinyl record collection.

## Features

- 🎵 **Sync your Discogs collection** by username — no authentication required for public collections
- 💿 **Clean album grid** with cover art prominently displayed
- 🔍 **Filter by genre and style** to find records by mood
- 💰 **Sort by value** using Discogs marketplace price data
- 🎬 **Album detail pages** with tracklist and YouTube preview embeds
- ⚡ **Cached data** — local SQLite database prevents hitting Discogs API rate limits
- 🔄 **One-click sync** to keep your local cache up to date

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.3)
- **Frontend**: Vue 3 + Inertia.js
- **Styling**: Tailwind CSS
- **Database**: SQLite (local cache)
- **API**: [Discogs REST API](https://www.discogs.com/developers/)

## Setup

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Optionally add your Discogs personal access token to `.env` for higher API rate limits:
```
DISCOGS_TOKEN=your_token_here
```
Get a token at: https://www.discogs.com/settings/developers

### 3. Set up the database

```bash
touch database/database.sqlite
php artisan migrate
```

### 4. Build assets and run

```bash
npm run build
php artisan serve
```

## Syncing Your Collection

### Via Web UI
1. Visit `/settings`
2. Enter your Discogs username
3. Click **Sync Collection**

### Via Artisan Command
```bash
php artisan discogs:sync your-discogs-username
```

## Keeping Data Fresh

Run the sync command on a schedule to keep prices and collection data updated:

```bash
# Add to crontab or Laravel scheduler in routes/console.php
php artisan discogs:sync
```

## Notes

- The Discogs API has rate limits (25–60 requests/min depending on authentication). The app adds a 0.5s delay between paginated collection pages to stay within limits.
- Release detail data (tracklist, videos) is cached for 7 days and refreshed on demand when you visit an album page.
- Price data (lowest/median/highest) is fetched on the settings sync page and requires a Discogs API token.

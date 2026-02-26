# Black Circles

A Laravel + Vue (Inertia.js) application to display and organize your [Discogs](https://www.discogs.com) vinyl record collection.

## Features

- 🎵 **Sync your Discogs collection** by username — no authentication required for public collections
- 🎲 **Mood-based discovery** — pick a mood (Chill, Energetic, Party, etc.) and get suggestions from your collection
- 💿 **Clean album grid** with cover art prominently displayed
- 🔍 **Filter by genre and style** to find records
- 💰 **Sort by value** using Discogs marketplace price data
- 🎬 **Album detail pages** with tracklist and YouTube preview embeds
- ⚡ **Cached data** — MySQL database prevents hitting Discogs API rate limits
- 🔄 **Artisan sync** to keep your local cache up to date

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.3)
- **Frontend**: Vue 3 + Inertia.js
- **Styling**: Tailwind CSS
- **Database**: MySQL 8.4 (via Docker/Sail)
- **API**: [Discogs REST API](https://www.discogs.com/developers/)

## Setup (with Laravel Sail)

Requires [Docker Desktop](https://www.docker.com/products/docker-desktop/).

### 1. Install PHP dependencies

```bash
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
```

### 3. Start Sail

```bash
sail up -d
```

### 4. Generate app key and run migrations

```bash
sail artisan key:generate
sail artisan migrate
```

### 5. Install Node dependencies and build assets

```bash
sail npm install
sail npm run dev
```

### 6. Configure Discogs

Add your Discogs username to `.env` (required for syncing and mood suggestions):

```
DISCOGS_USERNAME=your-discogs-username
```

Optionally add a personal access token for higher API rate limits:

```
DISCOGS_TOKEN=your_token_here
```

Get a token at: https://www.discogs.com/settings/developers

## Syncing Your Collection

With `DISCOGS_USERNAME` set in `.env`:

```bash
sail artisan discogs:sync
```

Or pass the username as an argument:

```bash
sail artisan discogs:sync your-discogs-username
```

## Keeping Data Fresh

Run the sync command on a schedule to keep prices and collection data updated:

```bash
# Add to routes/console.php (Laravel scheduler)
sail artisan discogs:sync
```

## Notes

- The Discogs API has rate limits (25–60 requests/min depending on authentication). The app adds a 0.5s delay between paginated collection pages to stay within limits.
- Release detail data (tracklist, videos) is cached for 7 days and refreshed on demand when you visit an album page.
- Price data (lowest/median/highest) is fetched during sync and when viewing release details; a Discogs API token improves rate limits.

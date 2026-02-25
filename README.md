# Black Circles

A Laravel + Vue (Inertia.js) application to display and organize your [Discogs](https://www.discogs.com) vinyl record collection.

## Features

- 🎵 **Sync your Discogs collection** by username — no authentication required for public collections
- 💿 **Clean album grid** with cover art prominently displayed
- 🔍 **Filter by genre and style** to find records by mood
- 💰 **Sort by value** using Discogs marketplace price data
- 🎬 **Album detail pages** with tracklist and YouTube preview embeds
- ⚡ **Cached data** — MySQL database prevents hitting Discogs API rate limits
- 🔄 **One-click sync** to keep your local cache up to date

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.4)
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
./vendor/bin/sail up -d
```

### 4. Generate app key and run migrations

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

### 5. Install Node dependencies and build assets

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Optionally add your Discogs personal access token to `.env` for higher API rate limits:
```
DISCOGS_TOKEN=your_token_here
```
Get a token at: https://www.discogs.com/settings/developers

## Syncing Your Collection

### Via Web UI
1. Visit `/settings`
2. Enter your Discogs username
3. Click **Sync Collection**

### Via Artisan Command
```bash
./vendor/bin/sail artisan discogs:sync your-discogs-username
```

## Keeping Data Fresh

Run the sync command on a schedule to keep prices and collection data updated:

```bash
# Add to routes/console.php (Laravel scheduler)
./vendor/bin/sail artisan discogs:sync
```

## Notes

- The Discogs API has rate limits (25–60 requests/min depending on authentication). The app adds a 0.5s delay between paginated collection pages to stay within limits.
- Release detail data (tracklist, videos) is cached for 7 days and refreshed on demand when you visit an album page.
- Price data (lowest/median/highest) is fetched on the settings sync page and requires a Discogs API token.

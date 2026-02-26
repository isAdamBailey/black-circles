<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\DiscogsService;
use Illuminate\Console\Command;

class SyncDiscogsCollection extends Command
{
    protected $signature = 'discogs:sync {username? : Discogs username to sync}';

    protected $description = 'Sync Discogs collection for a given username';

    public function handle(DiscogsService $discogs): int
    {
        $username = $this->argument('username') ?? Setting::discogsUsername();

        if ($username === '') {
            $this->error('No username. Set DISCOGS_USERNAME in .env or pass as argument: sail artisan discogs:sync username');

            return 1;
        }

        $this->info("Syncing collection for: {$username}");
        $result = $discogs->syncCollection($username);
        $this->info("Synced {$result['synced']} items.");

        return 0;
    }
}

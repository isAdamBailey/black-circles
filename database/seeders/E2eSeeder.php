<?php

namespace Database\Seeders;

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds a small, deterministic collection for the Playwright e2e suite.
 * Requires DISCOGS_USERNAME to be set in the environment so the "synced"
 * UI state (mood grid, collection grid, personality insight) renders.
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MoodSeeder::class);

        Setting::set(
            'personality_insight',
            'This collection reveals a listener who moves between moody post-punk and warm, laid-back jazz.'
        );

        $releases = [
            ['title' => 'Chill Sessions', 'artist' => 'Warm Grooves', 'genres' => ['Jazz'], 'styles' => ['Ambient']],
            ['title' => 'Night Drive', 'artist' => 'Nocturne', 'genres' => ['Rock'], 'styles' => ['Post-Punk', 'Gothic Rock']],
            ['title' => 'Sunday Morning', 'artist' => 'Warm Grooves', 'genres' => ['Folk, World, & Country'], 'styles' => ['Acoustic']],
            ['title' => 'Electric Pulse', 'artist' => 'Neon Static', 'genres' => ['Electronic'], 'styles' => ['Techno']],
            ['title' => 'Golden Hour', 'artist' => 'Sunbeam', 'genres' => ['Pop'], 'styles' => ['Disco']],
            ['title' => 'Deep Focus', 'artist' => 'Quiet Mind', 'genres' => ['Classical'], 'styles' => ['Modern Classical']],
        ];

        foreach ($releases as $data) {
            $release = DiscogsRelease::factory()
                ->withGenres($data['genres'])
                ->withStyles($data['styles'])
                ->create([
                    'title' => $data['title'],
                    'artist' => $data['artist'],
                    // Marks release detail data as already cached so visiting a
                    // release page in e2e doesn't trigger a live Discogs API call.
                    'release_data_cached_at' => now(),
                ]);

            DiscogsCollectionItem::factory()->for($release, 'release')->create();
        }
    }
}

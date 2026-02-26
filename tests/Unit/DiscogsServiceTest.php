<?php

use App\Models\DiscogsRelease;
use App\Services\DiscogsService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.discogs.token' => null]);
});

it('sets lowest_price from marketplace stats during enrich', function () {
    Http::fake([
        'api.discogs.com/releases/*' => Http::response([
            'id' => 123,
            'title' => 'Test',
            'year' => 2020,
            'lowest_price' => null,
            'num_for_sale' => 0,
        ], 200),
        'api.discogs.com/marketplace/stats/*' => Http::response([
            'lowest_price' => ['currency' => 'USD', 'value' => 12.99],
            'num_for_sale' => 3,
            'blocked_from_sale' => false,
        ], 200),
    ]);

    $release = DiscogsRelease::factory()->create([
        'discogs_id' => 999,
        'lowest_price' => null,
        'release_data_cached_at' => null,
    ]);

    $discogs = app(DiscogsService::class);
    $enriched = $discogs->enrichRelease($release);

    expect($enriched->lowest_price)->toBe(12.99)
        ->and($enriched->median_price)->toBeNull()
        ->and($enriched->highest_price)->toBeNull();
});

it('uses release api lowest_price as fallback when stats return null', function () {
    Http::fake([
        'api.discogs.com/releases/*' => Http::response([
            'id' => 123,
            'title' => 'Test',
            'year' => 2020,
            'lowest_price' => 8.50,
            'num_for_sale' => 1,
        ], 200),
        'api.discogs.com/marketplace/stats/*' => Http::response([
            'lowest_price' => null,
            'num_for_sale' => 0,
            'blocked_from_sale' => false,
        ], 200),
    ]);

    $release = DiscogsRelease::factory()->create([
        'discogs_id' => 888,
        'lowest_price' => null,
        'release_data_cached_at' => null,
    ]);

    $discogs = app(DiscogsService::class);
    $enriched = $discogs->enrichRelease($release);

    expect($enriched->lowest_price)->toBe(8.50);
});

it('preserves median and highest price during enrich', function () {
    Http::fake([
        'api.discogs.com/releases/*' => Http::response([
            'id' => 123,
            'title' => 'Test',
            'year' => 2020,
            'lowest_price' => 10.00,
        ], 200),
        'api.discogs.com/marketplace/stats/*' => Http::response([
            'lowest_price' => ['currency' => 'USD', 'value' => 10.00],
            'num_for_sale' => 2,
            'blocked_from_sale' => false,
        ], 200),
    ]);

    $release = DiscogsRelease::factory()->create([
        'discogs_id' => 777,
        'lowest_price' => 5.00,
        'median_price' => 15.00,
        'highest_price' => 50.00,
        'release_data_cached_at' => null,
    ]);

    $discogs = app(DiscogsService::class);
    $enriched = $discogs->enrichRelease($release);

    expect((float) $enriched->lowest_price)->toBe(10.0)
        ->and((float) $enriched->median_price)->toBe(15.0)
        ->and((float) $enriched->highest_price)->toBe(50.0);
});

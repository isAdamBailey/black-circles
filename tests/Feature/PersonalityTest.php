<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

it('personality:generate stores insight in settings', function () {
    config(['services.huggingface.token' => 'test-token']);

    Http::fake([
        'router.huggingface.co/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'This collection suggests an adventurous, open-minded listener.']],
            ],
        ], 200),
    ]);

    $release = DiscogsRelease::factory()
        ->withGenres(['Rock'])
        ->withStyles(['Post-Punk'])
        ->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->artisan('personality:generate')->assertExitCode(0);

    expect(Setting::get('personality_insight'))->toBe('This collection suggests an adventurous, open-minded listener.');
});

it('personality:generate skips when no huggingface token configured', function () {
    config(['services.huggingface.token' => '']);

    $this->artisan('personality:generate')->assertExitCode(0);

    expect(Setting::get('personality_insight'))->toBeNull();
});

it('personality:generate skips when collection is empty', function () {
    config(['services.huggingface.token' => 'test-token']);

    $this->artisan('personality:generate')->assertExitCode(0);

    expect(Setting::get('personality_insight'))->toBeNull();
});

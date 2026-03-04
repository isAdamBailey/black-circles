<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

it('renders the personality page', function () {
    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Personality/Show'));
});

it('returns stored insight from settings', function () {
    Setting::set('personality_insight', 'You are a creative and introspective person who values depth.');

    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Personality/Show')
            ->where('insight', 'You are a creative and introspective person who values depth.')
        );
});

it('returns empty string when no insight stored in settings', function () {
    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Personality/Show')
            ->where('insight', '')
        );
});

it('personality:generate stores insight in settings', function () {
    config(['services.huggingface.token' => 'test-token']);

    Http::fake([
        'router.huggingface.co/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'You are adventurous and open-minded.']],
            ],
        ], 200),
    ]);

    $release = DiscogsRelease::factory()
        ->withGenres(['Rock'])
        ->withStyles(['Post-Punk'])
        ->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->artisan('personality:generate')->assertExitCode(0);

    expect(Setting::get('personality_insight'))->toBe('You are adventurous and open-minded.');
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

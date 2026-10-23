<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Models\Setting;

function seedPersonalityCollection(): void
{
    $release = DiscogsRelease::factory()
        ->withGenres(['Rock'])
        ->withStyles(['Post-Punk'])
        ->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();
}

it('personality:generate stores insight in settings', function () {
    config(['services.anthropic.key' => 'test-key']);
    $this->fakeClaude([
        claudeMessage('This collection is built on wiry, restless post-punk.'),
    ]);
    seedPersonalityCollection();

    $this->artisan('personality:generate')
        ->expectsOutput('Insight saved.')
        ->assertExitCode(0);

    expect(Setting::get('personality_insight'))->toBe('This collection is built on wiry, restless post-punk.');
});

it('personality:generate fails and keeps the old insight when the claude api errors', function () {
    config(['services.anthropic.key' => 'test-key']);
    $this->fakeClaude([claudeError(400)]);
    seedPersonalityCollection();
    Setting::set('personality_insight', 'Previous insight.');

    $this->artisan('personality:generate')->assertExitCode(1);

    expect(Setting::get('personality_insight'))->toBe('Previous insight.');
});

it('personality:generate skips when no anthropic api key configured', function () {
    config(['services.anthropic.key' => '']);
    $history = $this->fakeClaude([]);
    seedPersonalityCollection();

    $this->artisan('personality:generate')
        ->expectsOutput('No Anthropic API key configured. Skipping.')
        ->assertExitCode(0);

    expect(Setting::get('personality_insight'))->toBeNull()
        ->and($history)->toHaveCount(0);
});

it('personality:generate skips when collection is empty', function () {
    config(['services.anthropic.key' => 'test-key']);

    $this->artisan('personality:generate')->assertExitCode(0);

    expect(Setting::get('personality_insight'))->toBeNull();
});

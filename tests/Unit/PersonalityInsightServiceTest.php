<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Services\PersonalityInsightService;

beforeEach(function () {
    config(['services.anthropic.key' => 'test-key']);
});

it('builds a prompt from top styles and genres', function () {
    $service = app(PersonalityInsightService::class);

    $prompt = $service->buildPrompt(
        [['name' => 'Post-Punk', 'count' => 8], ['name' => 'Ambient', 'count' => 3]],
        [['name' => 'Rock', 'count' => 10], ['name' => 'Electronic', 'count' => 5]]
    );

    expect($prompt)->toContain('Post-Punk')
        ->and($prompt)->toContain('Ambient')
        ->and($prompt)->toContain('Rock')
        ->and($prompt)->toContain('Electronic')
        ->and($prompt)->toContain('this collection')
        ->and($prompt)->not->toContain('Adam');
});

it('returns empty prompt when both styles and genres are empty', function () {
    $service = app(PersonalityInsightService::class);

    $prompt = $service->buildPrompt([], []);

    expect($prompt)->toBe('');
});

it('builds a valid prompt with only styles and no genres', function () {
    $service = app(PersonalityInsightService::class);

    $prompt = $service->buildPrompt([['name' => 'Post-Punk', 'count' => 5]]);

    expect($prompt)->toContain('Post-Punk')
        ->and($prompt)->not->toContain('Genres:');
});

it('generates personality insight via claude', function () {
    $history = $this->fakeClaude([
        claudeMessage('This collection leans into dark, angular guitar music.'),
    ]);

    $service = app(PersonalityInsightService::class);
    $result = $service->generatePersonalityInsight(
        [['name' => 'Post-Punk', 'count' => 5]],
        [['name' => 'Rock', 'count' => 3]]
    );

    $body = claudeRequestBody($history);
    expect($result)->toBe('This collection leans into dark, angular guitar music.')
        ->and($history)->toHaveCount(1)
        ->and($body['model'])->toBe('claude-opus-5-5')
        ->and($body['output_config'])->toBe(['effort' => 'low'])
        ->and($body['fallbacks'])->toBe('default')
        ->and($body['system'])->toContain('this collection')
        ->and($body['messages'][0]['content'])->toContain('Post-Punk')
        ->and($body)->not->toHaveKey('thinking')
        ->and($history[0]['request']->getHeaderLine('anthropic-beta'))->toBe('server-side-fallback-2026-07-01');
});

it('trims a response that is cut off mid-sentence back to the last complete sentence', function () {
    $this->fakeClaude([
        claudeMessage('This collection favors moody, atmospheric records. It leans toward long, unhurried'),
    ]);

    $service = app(PersonalityInsightService::class);
    $result = $service->generatePersonalityInsight(
        [['name' => 'Post-Punk', 'count' => 5]],
        [['name' => 'Rock', 'count' => 3]]
    );

    expect($result)->toBe('This collection favors moody, atmospheric records.');
});

it('returns empty string when the claude api errors', function () {
    $this->fakeClaude([claudeError(400)]);

    $result = app(PersonalityInsightService::class)->generatePersonalityInsight([['name' => 'Post-Punk', 'count' => 5]]);

    expect($result)->toBe('');
});

it('returns empty string when claude refuses', function () {
    $this->fakeClaude([claudeMessage('', 'refusal')]);

    $result = app(PersonalityInsightService::class)->generatePersonalityInsight([['name' => 'Post-Punk', 'count' => 5]]);

    expect($result)->toBe('');
});

it('returns empty string without calling claude when no api key is configured', function () {
    config(['services.anthropic.key' => '']);
    $history = $this->fakeClaude([claudeMessage('Should not be used.')]);

    $result = app(PersonalityInsightService::class)->generatePersonalityInsight([['name' => 'Post-Punk', 'count' => 5]]);

    expect($result)->toBe('')
        ->and($history)->toHaveCount(0);
});

it('returns empty string when styles and genres are empty', function () {
    $service = app(PersonalityInsightService::class);

    $result = $service->generatePersonalityInsight([], []);

    expect($result)->toBe('');
});

it('counts a release only once in topStyles when it has multiple collection items', function () {
    $release = DiscogsRelease::factory()->withStyles(['Post-Punk'])->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $result = app(PersonalityInsightService::class)->topStyles();

    expect(collect($result)->firstWhere('name', 'Post-Punk')['count'])->toBe(1);
});

it('counts a release only once in topGenres when it has multiple collection items', function () {
    $release = DiscogsRelease::factory()->withGenres(['Rock'])->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $result = app(PersonalityInsightService::class)->topGenres();

    expect(collect($result)->firstWhere('name', 'Rock')['count'])->toBe(1);
});

it('counts each distinct release separately in topStyles', function () {
    foreach (range(1, 3) as $i) {
        $release = DiscogsRelease::factory()->withStyles(['Post-Punk'])->create();
        DiscogsCollectionItem::factory()->for($release, 'release')->create();
    }

    $result = app(PersonalityInsightService::class)->topStyles();

    expect(collect($result)->firstWhere('name', 'Post-Punk')['count'])->toBe(3);
});

it('counts each distinct release separately in topGenres', function () {
    foreach (range(1, 3) as $i) {
        $release = DiscogsRelease::factory()->withGenres(['Rock'])->create();
        DiscogsCollectionItem::factory()->for($release, 'release')->create();
    }

    $result = app(PersonalityInsightService::class)->topGenres();

    expect(collect($result)->firstWhere('name', 'Rock')['count'])->toBe(3);
});

<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Models\Genre;
use App\Models\Style;
use App\Services\MoodKeywordMatcher;
use App\Services\ReleaseSuggestionService;
use App\Services\VibeSuggestionService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

beforeEach(function () {
    $release = DiscogsRelease::factory()
        ->withGenres(['Jazz', 'Electronic'])
        ->withStyles(['Ambient', 'Downtempo', 'Punk'])
        ->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();
});

/**
 * Mock the release pools so the test can see which tags reach fetchMatchingReleases.
 */
function expectTagPoolFetch(array $genres, array $styles, array $excludeStyles): void
{
    test()->mock(ReleaseSuggestionService::class, function ($mock) use ($genres, $styles, $excludeStyles) {
        $mock->shouldReceive('fetchMatchingReleases')->once()->with($genres, $styles, $excludeStyles, 40)->andReturn(collect());
        $mock->shouldReceive('scoutCollectionReleases')->andReturn(collect());
        $mock->shouldReceive('randomReleases')->andReturn(collect());
    });
}

function keywordTags(string $prompt): array
{
    return app(MoodKeywordMatcher::class)->matchTags($prompt, Genre::orderedNamesInCollection(), Style::orderedNamesInCollection());
}

it('uses claude-chosen tags to fetch matching releases', function () {
    config(['services.anthropic.key' => 'test-key']);
    $this->fakeClaude([
        claudeMessage(json_encode(['genres' => ['Jazz'], 'styles' => ['Ambient', 'Made Up'], 'exclude_styles' => ['Punk']])),
    ]);
    expectTagPoolFetch(['Jazz'], ['Ambient'], ['Punk']);

    app(VibeSuggestionService::class)->buildSuggestion('rainy sunday morning');
});

it('falls back to the keyword matcher when claude is unavailable', function (Closure $setup) {
    $prompt = 'something jazzy and chill';
    $expected = keywordTags($prompt);
    expect($expected['genres'])->not->toBeEmpty();

    $setup->call($this);
    expectTagPoolFetch($expected['genres'], $expected['styles'], []);

    app(VibeSuggestionService::class)->buildSuggestion($prompt);
})->with([
    'no key' => fn () => function () {
        config(['services.anthropic.key' => '']);
    },
    'api error' => fn () => function () {
        config(['services.anthropic.key' => 'test-key']);
        $this->fakeClaude([claudeError(500)]);
    },
    'timeout' => fn () => function () {
        config(['services.anthropic.key' => 'test-key']);
        $this->fakeClaude([new ConnectException('Operation timed out', new Request('POST', 'https://api.anthropic.com/v1/messages'))]);
    },
    'no usable tags' => fn () => function () {
        config(['services.anthropic.key' => 'test-key']);
        $this->fakeClaude([claudeMessage(json_encode(['genres' => ['Vaporwave'], 'styles' => [], 'exclude_styles' => []]))]);
    },
]);

it('never calls claude for mood presets with tags', function () {
    config(['services.anthropic.key' => 'test-key']);
    $history = $this->fakeClaude([]);

    $this->getJson(route('api.moods.suggest', 'chill'))->assertStatus(200);

    expect($history)->toHaveCount(0);
});

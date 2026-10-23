<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Models\Genre;
use App\Models\Style;
use App\Services\MoodKeywordMatcher;
use App\Services\ReleaseSuggestionService;
use App\Services\VibeSuggestionService;

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
function expectVibeTagPoolFetch(array $genres, array $styles, array $excludeStyles): void
{
    test()->mock(ReleaseSuggestionService::class, function ($mock) use ($genres, $styles, $excludeStyles) {
        $mock->shouldReceive('fetchMatchingReleases')->once()->with($genres, $styles, $excludeStyles, 40)->andReturn(collect());
        $mock->shouldReceive('scoutCollectionReleases')->andReturn(collect());
        $mock->shouldReceive('randomReleases')->andReturn(collect());
    });
}

it('uses claude-chosen tags to fetch matching releases', function () {
    config(['services.anthropic.key' => 'test-key']);
    $this->fakeClaude([
        claudeMessage(json_encode(['genres' => ['Jazz'], 'styles' => ['Ambient', 'Made Up'], 'exclude_styles' => ['Punk']])),
    ]);
    expectVibeTagPoolFetch(['Jazz'], ['Ambient'], ['Punk']);

    app(VibeSuggestionService::class)->buildSuggestion('rainy sunday morning');
});

it('uses keyword-matched tags when claude is unavailable', function () {
    config(['services.anthropic.key' => '']);
    $prompt = 'something jazzy and chill';
    $keywordTags = app(MoodKeywordMatcher::class)->matchTags($prompt, Genre::orderedNamesInCollection(), Style::orderedNamesInCollection());
    expect($keywordTags['genres'])->not->toBeEmpty();
    expectVibeTagPoolFetch($keywordTags['genres'], $keywordTags['styles'], []);

    app(VibeSuggestionService::class)->buildSuggestion($prompt);
});

it('never calls claude for mood presets with tags', function () {
    config(['services.anthropic.key' => 'test-key']);
    $history = $this->fakeClaude([]);

    $this->getJson(route('api.moods.suggest', 'chill'))->assertStatus(200);

    expect($history)->toHaveCount(0);
});

<?php

use App\Services\MoodKeywordMatcher;

it('directly matches a genre/style name mentioned in the prompt', function () {
    $matcher = new MoodKeywordMatcher;

    $result = $matcher->matchTags('something jazzy and chill', ['Jazz', 'Rock'], ['Ambient', 'Punk']);

    expect($result['genres'])->toContain('Jazz')
        ->and($result['styles'])->toContain('Ambient');
});

it('expands descriptive keywords with no literal genre words via the dictionary', function () {
    $matcher = new MoodKeywordMatcher;

    $result = $matcher->matchTags('something happy and upbeat', ['Pop', 'Rock'], ['Disco', 'Punk']);

    expect($result['genres'])->toContain('Pop')
        ->and($result['styles'])->toContain('Disco');
});

it('returns empty arrays when nothing in the prompt matches', function () {
    $matcher = new MoodKeywordMatcher;

    $result = $matcher->matchTags('xyz completely unrelated gibberish', ['Jazz'], ['Ambient']);

    expect($result['genres'])->toBe([])
        ->and($result['styles'])->toBe([]);
});

it('only returns tags present in the given collection vocabulary', function () {
    $matcher = new MoodKeywordMatcher;

    $result = $matcher->matchTags('something happy', ['Rock'], ['Punk']);

    expect($result['genres'])->not->toContain('Pop')
        ->and($result['styles'])->not->toContain('Disco');
});

it('matches case-insensitively', function () {
    $matcher = new MoodKeywordMatcher;

    $result = $matcher->matchTags('JAZZY tunes please', ['Jazz'], []);

    expect($result['genres'])->toContain('Jazz');
});

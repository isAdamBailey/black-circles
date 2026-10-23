<?php

use App\Services\MoodKeywordMatcher;
use App\Services\VibeTagSelector;
use Psr\Http\Message\ResponseInterface;

beforeEach(function () {
    config(['services.anthropic.key' => 'test-key']);
});

const GENRES = ['Jazz', 'Rock'];
const STYLES = ['Ambient', 'Punk'];

function claudeTags(array $genres, array $styles, array $excludeStyles = []): ResponseInterface
{
    return claudeMessage(json_encode(['genres' => $genres, 'styles' => $styles, 'exclude_styles' => $excludeStyles]));
}

function selectTags(string $prompt): array
{
    return app(VibeTagSelector::class)->selectTags($prompt, GENRES, STYLES);
}

it('returns the genres and styles claude picks from the collection lists', function () {
    $history = $this->fakeClaude([claudeTags(['Jazz'], ['Ambient'], ['Punk'])]);

    $tags = selectTags('rainy sunday morning');

    $body = claudeRequestBody($history);
    expect($tags)->toBe(['genres' => ['Jazz'], 'styles' => ['Ambient'], 'exclude_styles' => ['Punk']])
        ->and($body['output_config']['effort'])->toBe('low')
        ->and($body['output_config']['format']['type'])->toBe('json_schema')
        ->and($body['system'])->toContain("<styles>\nAmbient\nPunk\n</styles>")
        ->and($body['messages'][0]['content'])->toContain('rainy sunday morning')
        ->and($history[0]['options']['timeout'])->toBe(9.0);
});

it('drops tags that are not in the collection', function () {
    $this->fakeClaude([claudeTags(['Jazz', 'Vaporwave'], ['Lo-Fi', 'Ambient'], ['Polka'])]);

    expect(selectTags('rainy sunday morning'))->toBe(['genres' => ['Jazz'], 'styles' => ['Ambient'], 'exclude_styles' => []]);
});

it('never excludes a style it also selected', function () {
    $this->fakeClaude([claudeTags([], ['Ambient'], ['Ambient', 'Punk'])]);

    expect(selectTags('drifting')['exclude_styles'])->toBe(['Punk']);
});

it('falls back to the keyword matcher when claude has nothing usable', function (string $key, array $responses) {
    config(['services.anthropic.key' => $key]);
    $this->fakeClaude($responses);
    $prompt = 'something jazzy and chill';
    $keywordTags = app(MoodKeywordMatcher::class)->matchTags($prompt, GENRES, STYLES);

    expect($keywordTags['genres'])->not->toBeEmpty()
        ->and(selectTags($prompt))->toBe([...$keywordTags, 'exclude_styles' => []]);
})->with([
    'no key' => ['', []],
    'api error' => ['test-key', [claudeError(500)]],
    'timeout' => ['test-key', [claudeTimeout()]],
    'refusal' => ['test-key', [claudeMessage('', 'refusal')]],
    'truncated' => ['test-key', [claudeMessage('{"genres": ["Ja', 'max_tokens')]],
    'not json' => ['test-key', [claudeMessage('Jazz, Ambient')]],
    'no usable tags' => ['test-key', [claudeTags(['Vaporwave'], [])]],
]);

it('does not retry a failed call', function () {
    $history = $this->fakeClaude([claudeError(500), claudeTags(['Jazz'], [])]);

    selectTags('rainy sunday morning');

    expect($history)->toHaveCount(1);
});

it('skips claude for a minute after a failed call', function () {
    $history = $this->fakeClaude([claudeTimeout(), claudeTags(['Jazz'], [])]);

    selectTags('rainy sunday morning');
    selectTags('late night drive');
    $this->travel(61)->seconds();
    $tags = selectTags('late night drive');

    expect($history)->toHaveCount(2)
        ->and($tags['genres'])->toBe(['Jazz']);
});

it('caches choices per normalised prompt', function () {
    $history = $this->fakeClaude([claudeTags(['Jazz'], [])]);

    $first = selectTags('Rainy  Sunday morning');
    $second = selectTags(' rainy sunday MORNING ');

    expect($second)->toBe($first)
        ->and($history)->toHaveCount(1);
});

<?php

use App\Services\MoodKeywordMatcher;
use App\Services\VibeTagSelector;
use Psr\Http\Message\ResponseInterface;

beforeEach(function () {
    config(['services.anthropic.key' => 'test-key']);
});

const VIBE_GENRES = ['Jazz', 'Rock'];
const VIBE_STYLES = ['Ambient', 'Punk'];

function vibeTagsResponse(array $genres, array $styles, array $excludeStyles = []): ResponseInterface
{
    return claudeMessage(json_encode(['genres' => $genres, 'styles' => $styles, 'exclude_styles' => $excludeStyles]));
}

function selectVibeTags(string $prompt): array
{
    return app(VibeTagSelector::class)->selectTags($prompt, VIBE_GENRES, VIBE_STYLES);
}

it('returns the genres and styles claude picks from the collection lists', function () {
    $history = $this->fakeClaude([vibeTagsResponse(['Jazz'], ['Ambient'], ['Punk'])]);

    $tags = selectVibeTags('rainy sunday morning');

    $body = claudeRequestBody($history);
    expect($tags)->toBe(['genres' => ['Jazz'], 'styles' => ['Ambient'], 'exclude_styles' => ['Punk']])
        ->and($body['output_config']['effort'])->toBe('low')
        ->and($body['output_config']['format']['type'])->toBe('json_schema')
        ->and($body['system'])->toContain("<styles>\nAmbient\nPunk\n</styles>")
        ->and($body['messages'][0]['content'])->toContain('rainy sunday morning')
        ->and($history[0]['options']['timeout'])->toBe(9.0);
});

it('drops tags that are not in the collection', function () {
    $this->fakeClaude([vibeTagsResponse(['Jazz', 'Vaporwave'], ['Lo-Fi', 'Ambient'], ['Polka'])]);

    expect(selectVibeTags('rainy sunday morning'))->toBe(['genres' => ['Jazz'], 'styles' => ['Ambient'], 'exclude_styles' => []]);
});

it('never excludes a style it also selected', function () {
    $this->fakeClaude([vibeTagsResponse([], ['Ambient'], ['Ambient', 'Punk'])]);

    expect(selectVibeTags('drifting')['exclude_styles'])->toBe(['Punk']);
});

it('falls back to the keyword matcher when claude has nothing usable', function (string $key, array $responses) {
    config(['services.anthropic.key' => $key]);
    $this->fakeClaude($responses);
    $prompt = 'something jazzy and chill';
    $keywordTags = app(MoodKeywordMatcher::class)->matchTags($prompt, VIBE_GENRES, VIBE_STYLES);

    expect($keywordTags['genres'])->not->toBeEmpty()
        ->and(selectVibeTags($prompt))->toBe([...$keywordTags, 'exclude_styles' => []]);
})->with([
    'no key' => ['', []],
    'api error' => ['test-key', [claudeError(500)]],
    'timeout' => ['test-key', [claudeTimeout()]],
    'refusal' => ['test-key', [claudeMessage('', 'refusal')]],
    'truncated' => ['test-key', [claudeMessage('{"genres": ["Ja', 'max_tokens')]],
    'not json' => ['test-key', [claudeMessage('Jazz, Ambient')]],
    'no usable tags' => ['test-key', [vibeTagsResponse(['Vaporwave'], [])]],
]);

it('does not retry a failed call', function () {
    $history = $this->fakeClaude([claudeError(500), vibeTagsResponse(['Jazz'], [])]);

    selectVibeTags('rainy sunday morning');

    expect($history)->toHaveCount(1);
});

it('skips claude for a minute after an outage', function () {
    $history = $this->fakeClaude([claudeTimeout(), vibeTagsResponse(['Jazz'], [])]);

    selectVibeTags('rainy sunday morning');
    selectVibeTags('late night drive');
    $this->travel(61)->seconds();
    $tags = selectVibeTags('late night drive');

    expect($history)->toHaveCount(2)
        ->and($tags['genres'])->toBe(['Jazz']);
});

it('keeps using claude after a prompt-specific failure', function () {
    $history = $this->fakeClaude([claudeMessage('', 'refusal'), vibeTagsResponse(['Jazz'], [])]);

    selectVibeTags('rainy sunday morning');
    $tags = selectVibeTags('late night drive');

    expect($history)->toHaveCount(2)
        ->and($tags['genres'])->toBe(['Jazz']);
});

it('caches choices per normalised prompt', function () {
    $history = $this->fakeClaude([vibeTagsResponse(['Jazz'], [])]);

    $first = selectVibeTags('Rainy  Sunday morning');
    $second = selectVibeTags(' rainy sunday MORNING ');

    expect($second)->toBe($first)
        ->and($history)->toHaveCount(1);
});

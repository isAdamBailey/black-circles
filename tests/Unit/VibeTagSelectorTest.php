<?php

use App\Services\VibeTagSelector;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

beforeEach(function () {
    config(['services.anthropic.key' => 'test-key']);
});

it('returns the genres and styles claude picks from the collection lists', function () {
    $history = $this->fakeClaude([
        claudeMessage(json_encode(['genres' => ['Jazz'], 'styles' => ['Ambient'], 'exclude_styles' => ['Punk']])),
    ]);

    $tags = app(VibeTagSelector::class)->selectTags('rainy sunday morning', ['Jazz', 'Rock'], ['Ambient', 'Punk']);

    $body = claudeRequestBody($history);
    expect($tags)->toBe(['genres' => ['Jazz'], 'styles' => ['Ambient'], 'exclude_styles' => ['Punk']])
        ->and($body['output_config']['effort'])->toBe('low')
        ->and($body['output_config']['format']['type'])->toBe('json_schema')
        ->and($body['system'])->toContain("<styles>\nAmbient\nPunk\n</styles>")
        ->and($body['messages'][0]['content'])->toContain('rainy sunday morning');
});

it('drops tags that are not in the collection', function () {
    $this->fakeClaude([
        claudeMessage(json_encode(['genres' => ['Jazz', 'Vaporwave'], 'styles' => ['Lo-Fi', 'Ambient'], 'exclude_styles' => ['Polka']])),
    ]);

    $tags = app(VibeTagSelector::class)->selectTags('rainy sunday morning', ['Jazz', 'Rock'], ['Ambient', 'Punk']);

    expect($tags)->toBe(['genres' => ['Jazz'], 'styles' => ['Ambient'], 'exclude_styles' => []]);
});

it('never excludes a style it also selected', function () {
    $this->fakeClaude([
        claudeMessage(json_encode(['genres' => [], 'styles' => ['Ambient'], 'exclude_styles' => ['Ambient', 'Punk']])),
    ]);

    $tags = app(VibeTagSelector::class)->selectTags('drifting', ['Jazz'], ['Ambient', 'Punk']);

    expect($tags['exclude_styles'])->toBe(['Punk']);
});

it('returns null when the api errors, without retrying', function () {
    $history = $this->fakeClaude([
        claudeError(500),
        claudeMessage(json_encode(['genres' => ['Jazz'], 'styles' => [], 'exclude_styles' => []])),
    ]);

    $tags = app(VibeTagSelector::class)->selectTags('rainy sunday morning', ['Jazz'], ['Ambient']);

    expect($tags)->toBeNull()
        ->and($history)->toHaveCount(1);
});

it('returns null when the request times out', function () {
    $this->fakeClaude([
        new ConnectException('Operation timed out', new Request('POST', 'https://api.anthropic.com/v1/messages')),
    ]);

    expect(app(VibeTagSelector::class)->selectTags('rainy sunday morning', ['Jazz'], ['Ambient']))->toBeNull();
});

it('returns null when claude refuses or returns invalid json', function (string $text, string $stopReason) {
    $this->fakeClaude([claudeMessage($text, $stopReason)]);

    expect(app(VibeTagSelector::class)->selectTags('rainy sunday morning', ['Jazz'], ['Ambient']))->toBeNull();
})->with([
    'refusal' => ['', 'refusal'],
    'truncated' => ['{"genres": ["Ja', 'max_tokens'],
    'not json' => ['Jazz, Ambient', 'end_turn'],
]);

it('returns null without calling claude when no api key is configured', function () {
    config(['services.anthropic.key' => '']);
    $history = $this->fakeClaude([]);

    expect(app(VibeTagSelector::class)->selectTags('rainy sunday morning', ['Jazz'], ['Ambient']))->toBeNull()
        ->and($history)->toHaveCount(0);
});

it('caches choices per normalised prompt', function () {
    $history = $this->fakeClaude([
        claudeMessage(json_encode(['genres' => ['Jazz'], 'styles' => [], 'exclude_styles' => []])),
    ]);
    $selector = app(VibeTagSelector::class);

    $first = $selector->selectTags('Rainy  Sunday morning', ['Jazz'], ['Ambient']);
    $second = $selector->selectTags(' rainy sunday MORNING ', ['Jazz'], ['Ambient']);

    expect($second)->toBe($first)
        ->and($history)->toHaveCount(1);
});

it('does not cache failures', function () {
    $history = $this->fakeClaude([
        claudeError(500),
        claudeMessage(json_encode(['genres' => ['Jazz'], 'styles' => [], 'exclude_styles' => []])),
    ]);
    $selector = app(VibeTagSelector::class);

    $selector->selectTags('rainy sunday morning', ['Jazz'], ['Ambient']);
    $tags = $selector->selectTags('rainy sunday morning', ['Jazz'], ['Ambient']);

    expect($tags['genres'])->toBe(['Jazz'])
        ->and($history)->toHaveCount(2);
});

<?php

use App\Services\PersonalityInsightService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.huggingface.token' => 'test-token']);
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
        ->and($prompt)->toContain('personality traits');
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

it('generates personality insight via huggingface text generation', function () {
    Http::fake([
        'router.huggingface.co/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'You are a creative and introspective person.']],
            ],
        ], 200),
    ]);

    $service = app(PersonalityInsightService::class);
    $result = $service->generatePersonalityInsight(
        [['name' => 'Post-Punk', 'count' => 5]],
        [['name' => 'Rock', 'count' => 3]]
    );

    expect($result)->toBe('You are a creative and introspective person.');
});

it('returns empty string when styles and genres are empty', function () {
    $service = app(PersonalityInsightService::class);

    $result = $service->generatePersonalityInsight([], []);

    expect($result)->toBe('');
});

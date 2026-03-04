<?php

use App\Services\HuggingFaceService;
use App\Services\PersonalityInsightService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.huggingface.token' => 'test-token']);
});

it('builds a collection description from top genres and styles', function () {
    $service = app(PersonalityInsightService::class);

    $description = $service->buildCollectionDescription(
        [['name' => 'Rock', 'count' => 10], ['name' => 'Electronic', 'count' => 5]],
        [['name' => 'Post-Punk', 'count' => 8], ['name' => 'Ambient', 'count' => 3]]
    );

    expect($description)->toContain('Rock')
        ->and($description)->toContain('Electronic')
        ->and($description)->toContain('Post-Punk')
        ->and($description)->toContain('Ambient');
});

it('returns empty description when genres and styles are empty', function () {
    $service = app(PersonalityInsightService::class);

    $description = $service->buildCollectionDescription([], []);

    expect($description)->toBe('');
});

it('classifies traits via huggingface and returns scored pairs', function () {
    Http::fake([
        'router.huggingface.co/*' => Http::response([
            'labels' => ['creative and artistic', 'introspective and reflective'],
            'scores' => [0.88, 0.72],
        ], 200),
    ]);

    $service = app(PersonalityInsightService::class);
    $result = $service->classifyTraits('Genres: Rock, Electronic. Styles: Post-Punk, Ambient');

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('creative and artistic')
        ->and($result)->toHaveKey('introspective and reflective');
});

it('returns empty array when description is empty', function () {
    $service = app(PersonalityInsightService::class);

    $result = $service->classifyTraits('');

    expect($result)->toBe([]);
});

it('returns all trait labels', function () {
    $service = app(PersonalityInsightService::class);

    $labels = $service->traitLabels();

    expect($labels)->toBeArray()
        ->and(count($labels))->toBeGreaterThan(0);
});

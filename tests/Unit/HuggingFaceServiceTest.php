<?php

use App\Services\HuggingFaceService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.huggingface.token' => 'test-token']);
});

it('returns label-score pairs from successful API response', function () {
    Http::fake([
        'router.huggingface.co/*' => Http::response([
            'labels' => ['Post-Punk', 'Gothic Rock', 'Rock'],
            'scores' => [0.85, 0.72, 0.41],
        ], 200),
    ]);

    $service = app(HuggingFaceService::class);
    $result = $service->classifyText('dark moody music', ['Rock', 'Post-Punk', 'Gothic Rock', 'Pop']);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['Post-Punk', 'Gothic Rock', 'Rock'])
        ->and($result['Post-Punk'])->toBe(0.85)
        ->and($result['Gothic Rock'])->toBe(0.72)
        ->and($result['Rock'])->toBe(0.41)
        ->and($result)->not->toHaveKey('Pop');
});

it('filters out labels below threshold', function () {
    Http::fake([
        'router.huggingface.co/*' => Http::response([
            'labels' => ['A', 'B', 'C'],
            'scores' => [0.9, 0.1, 0.05],
        ], 200),
    ]);

    $service = app(HuggingFaceService::class);
    $result = $service->classifyText('test', ['A', 'B', 'C']);

    expect($result)->toHaveKey('A')
        ->and($result)->not->toHaveKey('B')
        ->and($result)->not->toHaveKey('C');
});

it('returns empty array when API returns error', function () {
    Http::fake([
        'router.huggingface.co/*' => Http::response(['error' => 'Model loading'], 503),
    ]);

    $service = app(HuggingFaceService::class);
    $result = $service->classifyText('test', ['Rock', 'Jazz']);

    expect($result)->toBe([]);
});

it('returns empty array when no token configured', function () {
    config(['services.huggingface.token' => '']);

    $service = app(HuggingFaceService::class);
    $result = $service->classifyText('test', ['Rock', 'Jazz']);

    expect($result)->toBe([]);
});

it('returns empty array when labels are empty', function () {
    $service = app(HuggingFaceService::class);
    $result = $service->classifyText('test', []);

    expect($result)->toBe([]);
});

it('partitions scored labels into genres and styles', function () {
    $service = app(HuggingFaceService::class);
    $scored = ['Post-Punk' => 0.9, 'Rock' => 0.8, 'Ambient' => 0.7, 'Pop' => 0.3];
    $allGenres = ['Rock', 'Pop', 'Jazz'];
    $allStyles = ['Post-Punk', 'Ambient', 'Disco'];

    $result = $service->partitionLabels($scored, $allGenres, $allStyles);

    expect($result['genres'])->toEqual(['Rock', 'Pop'])
        ->and($result['styles'])->toEqual(['Post-Punk', 'Ambient']);
});

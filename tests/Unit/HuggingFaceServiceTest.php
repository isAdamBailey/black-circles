<?php

use App\Services\HuggingFaceService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.huggingface.token' => 'test-token']);
});

it('returns generated text from chat completions endpoint', function () {
    Http::fake([
        'router.huggingface.co/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'You are creative and introspective.']],
            ],
        ], 200),
    ]);

    $service = app(HuggingFaceService::class);
    $result = $service->generateText('Describe the personality of someone who listens to Post-Punk and Ambient.');

    expect($result)->toBe('You are creative and introspective.');
});

it('returns empty string from generateText when API fails', function () {
    Http::fake([
        'router.huggingface.co/*' => Http::response(['error' => 'Model loading'], 503),
    ]);

    $service = app(HuggingFaceService::class);
    $result = $service->generateText('some prompt');

    expect($result)->toBe('');
});

it('returns empty string from generateText when no token configured', function () {
    config(['services.huggingface.token' => '']);

    $service = app(HuggingFaceService::class);
    $result = $service->generateText('some prompt');

    expect($result)->toBe('');
});

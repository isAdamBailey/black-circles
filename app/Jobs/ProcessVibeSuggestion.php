<?php

namespace App\Jobs;

use App\Services\VibeSuggestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessVibeSuggestion implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(
        public string $cacheKey,
        public string $prompt,
        public ?array $moodForUi = null,
        public ?array $moodPresetTags = null
    ) {}

    public function handle(VibeSuggestionService $vibeSuggestion): void
    {
        $existing = Cache::get($this->cacheKey);
        $queuedAt = is_array($existing) ? ($existing['queued_at'] ?? time()) : time();
        Cache::put($this->cacheKey, [
            'status' => 'processing',
            'queued_at' => $queuedAt,
        ], 600);

        try {
            $props = $vibeSuggestion->buildSuggestion($this->prompt, $this->moodForUi, $this->moodPresetTags);
            if ($props === null) {
                Cache::put($this->cacheKey, [
                    'status' => 'error',
                    'error' => 'Your collection is empty. Sync your Discogs collection to get suggestions.',
                ], 600);

                return;
            }

            Cache::put($this->cacheKey, [
                'status' => 'complete',
                'props' => $props,
            ], 600);
        } catch (\Throwable $e) {
            Log::warning('Vibe suggestion job failed', ['message' => $e->getMessage()]);
            Cache::put($this->cacheKey, [
                'status' => 'error',
                'error' => 'Could not generate suggestions. Try again in a moment.',
            ], 600);
        }
    }
}

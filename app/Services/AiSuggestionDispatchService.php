<?php

namespace App\Services;

use App\Http\Controllers\VibeController;
use App\Jobs\ProcessVibeSuggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AiSuggestionDispatchService
{
    /**
     * @param  array{slug: string, label: string, emoji: string}|null  $moodForUi
     * @param  array{genres: array<int, string>, styles: array<int, string>, exclude_styles: array<int, string>}|null  $moodPresetTags
     */
    public function begin(string $prompt, ?array $moodForUi = null, ?array $moodPresetTags = null): RedirectResponse
    {
        $id = (string) Str::uuid();
        $cacheKey = VibeController::cacheKey($id);
        $pollTimeoutSeconds = max(1, (int) config('services.vibe.poll_timeout_seconds', 600));
        $cacheTtlSeconds = $pollTimeoutSeconds + 30;

        Cache::put($cacheKey, ['status' => 'queued', 'queued_at' => time()], $cacheTtlSeconds);
        ProcessVibeSuggestion::dispatch($cacheKey, $prompt, $moodForUi, $moodPresetTags);

        return redirect()->route('vibe.wait', $id);
    }
}

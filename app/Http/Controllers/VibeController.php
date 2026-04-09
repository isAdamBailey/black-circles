<?php

namespace App\Http\Controllers;

use App\Services\AiSuggestionDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class VibeController extends Controller
{
    public function __construct(
        private AiSuggestionDispatchService $aiSuggestionDispatch
    ) {}

    public static function cacheKey(string $token): string
    {
        return 'vibe_suggestion:'.$token;
    }

    public function suggest(Request $request): RedirectResponse
    {
        if (empty(config('services.huggingface.token'))) {
            return redirect()->route('home')->with('error', 'Hugging Face API is not configured. Add HUGGINGFACE_API_TOKEN to .env');
        }

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        return $this->aiSuggestionDispatch->begin(trim($validated['prompt']), null);
    }

    public function wait(string $token): Response|RedirectResponse
    {
        $data = Cache::get(self::cacheKey($token));
        if (is_array($data) && ($data['status'] ?? '') === 'complete') {
            return redirect()->route('vibe.result', $token);
        }
        if (is_array($data) && ($data['status'] ?? '') === 'error') {
            return redirect()->route('home')->with('error', $data['error'] ?? 'Something went wrong.');
        }

        return Inertia::render('Mood/VibeWait', ['token' => $token]);
    }

    public function poll(string $token): JsonResponse
    {
        $key = self::cacheKey($token);
        $data = Cache::get($key);
        if (! is_array($data)) {
            return response()->json([
                'ready' => false,
                'error' => null,
                'cache_miss' => true,
            ]);
        }

        $configured = (int) config('services.vibe.poll_timeout_seconds', 180);
        $staleSeconds = $configured > 0 ? $configured : 180;
        $queuedAt = $data['queued_at'] ?? null;
        $queuedTs = is_int($queuedAt) ? $queuedAt : (is_numeric($queuedAt) ? (int) $queuedAt : null);
        $status = $data['status'] ?? '';

        if (in_array($status, ['queued', 'processing'], true) && $queuedTs === null) {
            Cache::forget($key);

            return response()->json([
                'ready' => true,
                'redirect' => null,
                'error' => 'This search session is no longer valid. Go back to Discover and try again.',
            ]);
        }

        if (in_array($status, ['queued', 'processing'], true)
            && (time() - $queuedTs) > $staleSeconds) {
            Cache::forget($key);

            return response()->json([
                'ready' => true,
                'redirect' => null,
                'error' => 'This is taking longer than expected. Please wait a moment and try again.',
            ]);
        }

        if ($status === 'complete') {
            return response()->json([
                'ready' => true,
                'redirect' => route('vibe.result', $token),
                'error' => null,
            ]);
        }
        if ($status === 'error') {
            return response()->json([
                'ready' => true,
                'redirect' => null,
                'error' => $data['error'] ?? 'Something went wrong.',
            ]);
        }

        return response()->json(['ready' => false, 'error' => null]);
    }

    public function result(string $token): Response|RedirectResponse
    {
        $cacheKey = self::cacheKey($token);
        $data = Cache::pull($cacheKey);
        if (! is_array($data) || ($data['status'] ?? '') !== 'complete' || ! isset($data['props'])) {
            return redirect()->route('home')->with('error', 'That suggestion expired or could not be loaded. Try again.');
        }

        return Inertia::render('Mood/Suggest', $data['props']);
    }
}

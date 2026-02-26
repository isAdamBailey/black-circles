<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Style;
use App\Services\HuggingFaceService;
use App\Services\ReleaseSuggestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VibeController extends Controller
{
    public function __construct(
        private HuggingFaceService $huggingFace,
        private ReleaseSuggestionService $releaseSuggestion
    ) {}

    public function suggest(Request $request): Response|RedirectResponse
    {
        $token = config('services.huggingface.token');
        if (empty($token)) {
            return redirect()->route('home')->with('error', 'Hugging Face API is not configured. Add HUGGINGFACE_API_TOKEN to .env');
        }

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:500'],
        ]);
        $prompt = trim($validated['prompt']);

        $allGenres = Genre::orderedNames();
        $allStyles = Style::orderedNames();
        $labels = array_values(array_unique(array_merge($allGenres, $allStyles)));

        $directMatches = $this->releaseSuggestion->fetchReleasesMatchingArtistOrTitle($prompt, 5);

        $pool = collect();
        if ($directMatches->isNotEmpty()) {
            $pool = $directMatches;
        } elseif (empty($labels)) {
            $pool = $this->releaseSuggestion->randomReleases(5);
        } else {
            $scored = $this->huggingFace->classifyText($prompt, $labels);
            $partitioned = $this->huggingFace->partitionLabels($scored, $allGenres, $allStyles);
            $genres = $partitioned['genres'];
            $styles = $partitioned['styles'];

            if (! empty($genres) || ! empty($styles)) {
                $pool = $this->releaseSuggestion->fetchMatchingReleases($genres, $styles, [], 5);
            }
            if ($pool->isEmpty()) {
                $pool = $this->releaseSuggestion->randomReleases(5);
            }
        }

        if ($pool->isEmpty()) {
            return redirect()->route('home')->with('error', 'Your collection is empty. Sync your Discogs collection to get suggestions.');
        }

        $primary = $pool->first();
        $backups = $pool->skip(1)->values()->map(fn ($r) => $this->releaseSuggestion->formatRelease($r));

        return Inertia::render('Mood/Suggest', [
            'mood' => [
                'slug' => 'vibe',
                'label' => $prompt,
                'emoji' => '🎵',
                'vibePrompt' => $prompt,
            ],
            'primary' => $this->releaseSuggestion->formatRelease($primary),
            'backups' => $backups,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Mood;
use App\Models\Setting;
use App\Services\ReleaseSuggestionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MoodController extends Controller
{
    public function __construct(
        private ReleaseSuggestionService $releaseSuggestion
    ) {}

    public function index(): Response
    {
        $moods = Mood::orderBy('sort_order')->get()->map(fn (Mood $m) => [
            'slug' => $m->slug,
            'label' => $m->label,
            'emoji' => $m->emoji,
        ])->values();

        return Inertia::render('Home', [
            'moods' => $moods,
            'username' => Setting::discogsUsername(),
        ]);
    }

    public function suggest(string $mood): Response|RedirectResponse
    {
        $moodKey = strtolower($mood);
        $moodModel = Mood::where('slug', $moodKey)->with(['genres', 'styles', 'excludeStyles'])->first();

        if (! $moodModel) {
            return redirect()->route('home');
        }

        $genres = $moodModel->getGenreNames();
        $styles = $moodModel->getStyleNames();
        $excludeStyles = $moodModel->getExcludeStyleNames();
        $pool = $this->releaseSuggestion->fetchMatchingReleases($genres, $styles, $excludeStyles, 5);

        if ($pool->isEmpty()) {
            $pool = $this->releaseSuggestion->randomReleases(5);
        }

        if ($pool->isEmpty()) {
            return redirect()->route('home')->with('error', 'Your collection is empty. Sync your Discogs collection to get suggestions.');
        }

        $primary = $pool->first();
        $backups = $pool->skip(1)->values()->map(fn ($r) => $this->releaseSuggestion->formatRelease($r));
        $primaryFormatted = $this->releaseSuggestion->formatRelease($primary);

        return Inertia::render('Mood/Suggest', [
            'mood' => [
                'slug' => $moodKey,
                'label' => $moodModel->label,
                'emoji' => $moodModel->emoji,
            ],
            'primary' => $primaryFormatted,
            'backups' => $backups,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Mood;
use App\Models\Setting;
use App\Services\VibeSuggestionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MoodController extends Controller
{
    public function __construct(
        private VibeSuggestionService $vibeSuggestion
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
            'insight' => Setting::get('personality_insight', ''),
        ]);
    }

    public function suggest(string $mood): Response|RedirectResponse
    {
        $moodKey = strtolower($mood);
        $moodModel = Mood::where('slug', $moodKey)->with(['genres', 'styles', 'excludeStyles'])->first();

        if (! $moodModel) {
            return redirect()->route('home');
        }

        $props = $this->vibeSuggestion->buildSuggestion(
            $moodModel->aiPromptString(),
            [
                'slug' => $moodModel->slug,
                'label' => $moodModel->label,
                'emoji' => $moodModel->emoji,
            ],
            [
                'genres' => $moodModel->getGenreNames(),
                'styles' => $moodModel->getStyleNames(),
                'exclude_styles' => $moodModel->getExcludeStyleNames(),
            ]
        );

        if ($props === null) {
            return redirect()->route('home')->with('error', 'Your collection is empty. Sync your Discogs collection to get suggestions.');
        }

        return Inertia::render('Mood/Suggest', $props);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\PersonalityInsightService;
use Inertia\Inertia;
use Inertia\Response;

class PersonalityController extends Controller
{
    public function __construct(
        private PersonalityInsightService $personalityInsight
    ) {}

    public function show(): Response
    {
        $topGenres = $this->personalityInsight->topGenres();
        $topStyles = $this->personalityInsight->topStyles();
        $collectionSize = $this->personalityInsight->collectionSize();

        $insight = '';
        $hasToken = ! empty(config('services.huggingface.token'));

        if ($hasToken && $collectionSize > 0) {
            $insight = $this->personalityInsight->generatePersonalityInsight($topStyles, $topGenres);
        }

        return Inertia::render('Personality/Show', [
            'topGenres' => $topGenres,
            'topStyles' => $topStyles,
            'collectionSize' => $collectionSize,
            'insight' => $insight,
            'hasToken' => $hasToken,
            'username' => Setting::discogsUsername(),
        ]);
    }
}

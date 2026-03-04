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

        $traits = [];
        $hasToken = ! empty(config('services.huggingface.token'));

        if ($hasToken && $collectionSize > 0) {
            $description = $this->personalityInsight->buildCollectionDescription($topGenres, $topStyles);
            $traits = $this->personalityInsight->classifyTraits($description);
        }

        return Inertia::render('Personality/Show', [
            'topGenres' => $topGenres,
            'topStyles' => $topStyles,
            'collectionSize' => $collectionSize,
            'traits' => $traits,
            'hasToken' => $hasToken,
            'username' => Setting::discogsUsername(),
        ]);
    }
}

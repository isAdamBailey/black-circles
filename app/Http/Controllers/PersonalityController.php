<?php

namespace App\Http\Controllers;

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
        $topStyles = $this->personalityInsight->topStyles();
        $topGenres = $this->personalityInsight->topGenres();
        $collectionSize = $this->personalityInsight->collectionSize();
        $hasToken = ! empty(config('services.huggingface.token'));

        $insight = ($hasToken && $collectionSize > 0)
            ? $this->personalityInsight->generatePersonalityInsight($topStyles, $topGenres)
            : '';

        return Inertia::render('Personality/Show', [
            'insight' => $insight,
        ]);
    }
}

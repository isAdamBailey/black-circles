<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuggestionResource;
use App\Models\Mood;
use App\Services\VibeSuggestionService;
use Illuminate\Http\JsonResponse;

class MoodController extends Controller
{
    public function __construct(
        private VibeSuggestionService $vibeSuggestion
    ) {}

    public function suggest(string $mood): JsonResponse
    {
        $moodModel = Mood::where('slug', strtolower($mood))
            ->with(['genres', 'styles', 'excludeStyles'])
            ->first();

        if (! $moodModel) {
            return response()->json(['message' => 'Mood not found.'], 404);
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
            return response()->json([
                'message' => 'Your collection is empty. Sync your Discogs collection to get suggestions.',
            ], 422);
        }

        return (new SuggestionResource($props))->response();
    }
}

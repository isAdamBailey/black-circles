<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuggestionResource;
use App\Services\VibeSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VibeController extends Controller
{
    public function __construct(
        private VibeSuggestionService $vibeSuggestion
    ) {}

    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $props = $this->vibeSuggestion->buildSuggestion(trim($validated['prompt']));

        if ($props === null) {
            return response()->json([
                'message' => 'Your collection is empty. Sync your Discogs collection to get suggestions.',
            ], 422);
        }

        return (new SuggestionResource($props))->response();
    }
}

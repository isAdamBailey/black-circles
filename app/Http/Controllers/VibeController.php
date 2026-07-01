<?php

namespace App\Http\Controllers;

use App\Services\VibeSuggestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VibeController extends Controller
{
    public function __construct(
        private VibeSuggestionService $vibeSuggestion
    ) {}

    public function suggest(Request $request): Response|RedirectResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $props = $this->vibeSuggestion->buildSuggestion(trim($validated['prompt']));

        if ($props === null) {
            return redirect()->route('home')->with('error', 'Your collection is empty. Sync your Discogs collection to get suggestions.');
        }

        return Inertia::render('Mood/Suggest', $props);
    }
}

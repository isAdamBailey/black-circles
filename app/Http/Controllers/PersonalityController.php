<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

class PersonalityController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Personality/Show', [
            'insight' => Setting::get('personality_insight', ''),
        ]);
    }
}

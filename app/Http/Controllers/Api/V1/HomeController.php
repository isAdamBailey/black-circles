<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomeResource;
use App\Models\Mood;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        $moods = Mood::orderBy('sort_order')->get();

        return (new HomeResource([
            'moods' => $moods,
            'username' => Setting::discogsUsername(),
            'insight' => Setting::get('personality_insight', ''),
        ]))->response();
    }
}

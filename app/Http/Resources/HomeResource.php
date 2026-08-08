<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'moods' => MoodResource::collection($this->resource['moods']),
            'username' => $this->resource['username'],
            'insight' => $this->resource['insight'],
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'mood' => $this->resource['mood'],
            'primary' => $this->resource['primary'],
            'backups' => $this->resource['backups'],
        ];
    }
}

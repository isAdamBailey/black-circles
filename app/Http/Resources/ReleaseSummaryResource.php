<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleaseSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'discogs_id' => $this->discogs_id,
            'title' => $this->title,
            'artist' => $this->artist,
            'cover_image' => $this->cover_image,
            'thumb' => $this->thumb,
            'year' => $this->year,
            'lowest_price' => $this->lowest_price,
            'genres' => $this->whenLoaded('genres', fn () => $this->genres->pluck('name')),
            'styles' => $this->whenLoaded('styles', fn () => $this->styles->pluck('name')),
            'collection_item' => $this->whenLoaded('collectionItem', fn () => $this->collectionItem ? [
                'rating' => $this->collectionItem->rating,
                'date_added' => $this->collectionItem->date_added,
            ] : null),
        ];
    }
}

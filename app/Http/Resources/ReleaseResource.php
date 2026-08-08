<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'discogs_id' => $this->discogs_id,
            'title' => $this->title,
            'artist' => $this->artist,
            'label' => $this->label,
            'catalog_number' => $this->catalog_number,
            'year' => $this->year,
            'cover_image' => $this->cover_image,
            'thumb' => $this->thumb,
            'images' => $this->images,
            'formats' => $this->formats,
            'tracklist' => $this->tracklist,
            'videos' => $this->videos,
            'lowest_price' => $this->lowest_price,
            'median_price' => $this->median_price,
            'highest_price' => $this->highest_price,
            'discogs_uri' => $this->discogs_uri,
            'notes' => $this->notes,
            'genres' => $this->whenLoaded('genres', fn () => $this->genres->pluck('name')),
            'styles' => $this->whenLoaded('styles', fn () => $this->styles->pluck('name')),
            'collection_item' => $this->whenLoaded('collectionItem', fn () => $this->collectionItem ? [
                'rating' => $this->collectionItem->rating,
                'date_added' => $this->collectionItem->date_added,
            ] : null),
        ];
    }
}

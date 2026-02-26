<?php

namespace App\Services;

use App\Models\DiscogsRelease;
use Illuminate\Support\Collection;

class ReleaseSuggestionService
{
    public function fetchMatchingReleases(
        array $genres,
        array $styles,
        array $excludeStyles,
        int $limit
    ): Collection {
        $query = DiscogsRelease::query()
            ->whereHas('collectionItem')
            ->where(function ($q) use ($genres, $styles) {
                if (! empty($genres)) {
                    $q->whereHasGenres($genres);
                }
                if (! empty($styles)) {
                    $q->orWhereHasStyles($styles);
                }
            })
            ->whereDoesntHaveStyles($excludeStyles);

        return $query
            ->with(['genres', 'styles'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function randomReleases(int $limit): Collection
    {
        return DiscogsRelease::query()
            ->whereHas('collectionItem')
            ->with(['genres', 'styles'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function formatRelease(DiscogsRelease $release): array
    {
        return [
            'discogs_id' => $release->discogs_id,
            'title' => $release->title,
            'artist' => $release->artist,
            'cover_image' => $release->cover_image,
            'thumb' => $release->thumb,
            'year' => $release->year,
            'genres' => $release->genres->pluck('name')->toArray(),
            'styles' => $release->styles->pluck('name')->toArray(),
        ];
    }
}

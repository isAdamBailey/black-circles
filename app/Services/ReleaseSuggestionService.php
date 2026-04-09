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
                if (! empty($genres) && ! empty($styles)) {
                    $q->where(function ($inner) use ($genres, $styles) {
                        $inner->whereHasGenres($genres)
                            ->orWhereHasStyles($styles);
                    });
                } elseif (! empty($genres)) {
                    $q->whereHasGenres($genres);
                } elseif (! empty($styles)) {
                    $q->whereHasStyles($styles);
                }
            })
            ->whereDoesntHaveStyles($excludeStyles);

        return $query
            ->with(['genres', 'styles'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function scoutCollectionReleases(string $prompt, int $limit): Collection
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            return collect();
        }

        try {
            $hits = DiscogsRelease::search($prompt)->take(max($limit * 2, $limit))->get();
            if ($hits->isEmpty()) {
                return collect();
            }

            $orderedIds = $hits->pluck('id')->all();
            $inCollection = DiscogsRelease::query()
                ->whereIn('id', $orderedIds)
                ->whereHas('collectionItem')
                ->pluck('id')
                ->all();
            $inSet = array_flip($inCollection);
            $filteredIds = array_values(array_filter($orderedIds, fn (int|string $id) => isset($inSet[$id])));
            $filteredIds = array_slice($filteredIds, 0, $limit);
            if ($filteredIds === []) {
                return collect();
            }

            return DiscogsRelease::query()
                ->whereIn('id', $filteredIds)
                ->with(['genres', 'styles'])
                ->get()
                ->sortBy(fn (DiscogsRelease $r) => array_search($r->id, $filteredIds, true))
                ->values();
        } catch (\Throwable) {
            return collect();
        }
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

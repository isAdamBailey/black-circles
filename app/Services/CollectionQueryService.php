<?php

namespace App\Services;

use App\Models\DiscogsRelease;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CollectionQueryService
{
    public function build(Request $request): Builder
    {
        $query = DiscogsRelease::query()
            ->whereHas('collectionItem')
            ->with('collectionItem');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('artist', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%");
            });
        }

        $query->whereHasGenres((array) $request->get('genres', []));
        $query->whereHasStyles((array) $request->get('styles', []));

        [$sort, $direction] = $this->sort($request);
        $dirSql = $direction === 'desc' ? 'DESC' : 'ASC';

        if ($sort === 'value') {
            $query->reorder()->orderByRaw("CASE WHEN discogs_releases.lowest_price IS NULL THEN 1 ELSE 0 END ASC, discogs_releases.lowest_price {$dirSql}, discogs_releases.id ASC");
        } elseif ($sort === 'year') {
            $query->orderByRaw("CASE WHEN discogs_releases.year IS NULL OR discogs_releases.year = 0 THEN 1 ELSE 0 END ASC, discogs_releases.year {$dirSql}, discogs_releases.id ASC");
        } elseif ($sort === 'artist') {
            $query->orderBy('discogs_releases.artist', $direction);
        } elseif ($sort === 'title') {
            $query->orderBy('discogs_releases.title', $direction);
        } else {
            $query->join('discogs_collection_items', 'discogs_releases.discogs_id', '=', 'discogs_collection_items.discogs_release_id')
                ->orderBy('discogs_collection_items.date_added', $direction)
                ->select('discogs_releases.*');
        }

        return $query;
    }

    public function sort(Request $request): array
    {
        $sort = trim((string) $request->get('sort', 'value'));
        $direction = in_array($request->get('direction'), ['asc', 'desc']) ? $request->get('direction') : 'desc';

        return [$sort, $direction];
    }
}

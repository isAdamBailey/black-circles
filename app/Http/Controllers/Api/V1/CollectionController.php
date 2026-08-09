<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionIndexResource;
use App\Http\Resources\ReleaseResource;
use App\Models\DiscogsRelease;
use App\Models\Genre;
use App\Models\Setting;
use App\Models\Style;
use App\Services\CollectionQueryService;
use App\Services\DiscogsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(Request $request, CollectionQueryService $collectionQuery): JsonResponse
    {
        $query = $collectionQuery->build($request);
        [$sort, $direction] = $collectionQuery->sort($request);

        $releases = $query->paginate(48)->withQueryString();

        return (new CollectionIndexResource($releases, [
            'filters' => array_merge(
                $request->only(['search', 'genres', 'styles']),
                ['sort' => $sort, 'direction' => $direction]
            ),
            'allGenres' => Genre::orderedNames(),
            'allStyles' => Style::orderedNames(),
            'username' => Setting::discogsUsername(),
            'lastSynced' => Setting::get('collection_last_synced'),
        ]))->response();
    }

    public function show(int $id, DiscogsService $discogs): JsonResponse
    {
        $release = DiscogsRelease::where('discogs_id', $id)
            ->with(['collectionItem', 'genres', 'styles'])
            ->firstOrFail();

        $release = $discogs->enrichRelease($release);
        $release->load(['genres', 'styles', 'collectionItem']);

        return (new ReleaseResource($release))->response();
    }

    public function random(): JsonResponse
    {
        $release = DiscogsRelease::query()
            ->whereHas('collectionItem')
            ->with(['genres', 'styles', 'collectionItem'])
            ->inRandomOrder()
            ->first();

        if (! $release) {
            return response()->json([
                'message' => 'Your collection is empty. Sync your Discogs collection to get suggestions.',
            ], 404);
        }

        return (new ReleaseResource($release))->response();
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        if ($q === '') {
            return response()->json(['data' => []]);
        }

        try {
            $releases = DiscogsRelease::search($q)
                ->take(10)
                ->get();
        } catch (\Throwable) {
            $search = $q;
            $releases = DiscogsRelease::query()
                ->whereHas('collectionItem')
                ->where(fn ($query) => $query->where('title', 'like', "%{$search}%")
                    ->orWhere('artist', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%"))
                ->limit(10)
                ->get();
        }

        $data = $releases->map(fn ($r) => [
            'id' => $r->id,
            'discogs_id' => $r->discogs_id,
            'title' => $r->title,
            'artist' => $r->artist,
            'thumb' => $r->thumb,
        ]);

        return response()->json(['data' => $data]);
    }
}

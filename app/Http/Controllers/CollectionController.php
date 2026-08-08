<?php

namespace App\Http\Controllers;

use App\Models\DiscogsRelease;
use App\Models\Genre;
use App\Models\Setting;
use App\Models\Style;
use App\Services\CollectionQueryService;
use App\Services\DiscogsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function random(): RedirectResponse
    {
        $release = DiscogsRelease::query()
            ->whereHas('collectionItem')
            ->inRandomOrder()
            ->first();

        if (! $release) {
            return redirect()->route('home')->with('error', 'Your collection is empty. Sync your Discogs collection to get suggestions.');
        }

        return redirect()->route('collection.show', $release->discogs_id);
    }

    public function index(Request $request, CollectionQueryService $collectionQuery): Response
    {
        $query = $collectionQuery->build($request);
        [$sort, $direction] = $collectionQuery->sort($request);

        $allGenres = Genre::orderedNames();
        $allStyles = Style::orderedNames();

        return Inertia::render('Collection/Index', [
            'releases' => Inertia::scroll(fn () => $query->paginate(48)->withQueryString()),
            'filters' => array_merge(
                $request->only(['search', 'genres', 'styles']),
                ['sort' => $sort, 'direction' => $direction]
            ),
            'allGenres' => $allGenres,
            'allStyles' => $allStyles,
            'username' => Setting::discogsUsername(),
            'lastSynced' => Setting::get('collection_last_synced'),
        ]);
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

    public function show(int $id, DiscogsService $discogs): Response
    {
        $release = DiscogsRelease::where('discogs_id', $id)
            ->with(['collectionItem', 'genres', 'styles'])
            ->firstOrFail();

        $release = $discogs->enrichRelease($release);

        // Reload relationships in case enrichRelease called fresh()
        $release->load(['genres', 'styles']);

        return Inertia::render('Collection/Show', [
            'release' => array_merge($release->toArray(), [
                // Return genre/style names as plain string arrays so the Vue
                // receives the same shape it always expected.
                'genres' => $release->genres->pluck('name'),
                'styles' => $release->styles->pluck('name'),
            ]),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DiscogsRelease;
use App\Models\Genre;
use App\Models\Setting;
use App\Models\Style;
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

    public function index(Request $request): Response
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

        if ($genres = $request->get('genres')) {
            $query->whereHas('genres', fn ($q) => $q->whereIn('name', (array) $genres));
        }

        if ($styles = $request->get('styles')) {
            $query->whereHas('styles', fn ($q) => $q->whereIn('name', (array) $styles));
        }

        $sort = trim((string) $request->get('sort', 'value'));
        $direction = in_array($request->get('direction'), ['asc', 'desc']) ? $request->get('direction') : 'desc';
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

        $allGenres = Genre::orderBy('name')->pluck('name');
        $allStyles = Style::orderBy('name')->pluck('name');

        return Inertia::render('Collection/Index', [
            'releases' => Inertia::scroll(fn () => $query->paginate(48)->withQueryString()),
            'filters' => $request->only(['search', 'genres', 'styles', 'sort', 'direction']),
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

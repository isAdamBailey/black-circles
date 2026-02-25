<?php

namespace App\Http\Controllers;

use App\Models\DiscogsRelease;
use App\Models\Genre;
use App\Models\Setting;
use App\Models\Style;
use App\Services\DiscogsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DiscogsRelease::query()->with('collectionItem');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('artist', 'like', "%{$search}%")
                  ->orWhere('label', 'like', "%{$search}%");
            });
        }

        if ($genres = $request->get('genres')) {
            $query->whereHas('genres', fn($q) => $q->whereIn('name', (array) $genres));
        }

        if ($styles = $request->get('styles')) {
            $query->whereHas('styles', fn($q) => $q->whereIn('name', (array) $styles));
        }

        $sort = $request->get('sort', 'date_added');
        $direction = $request->get('direction', 'desc');

        if ($sort === 'value') {
            $query->orderBy('median_price', $direction === 'asc' ? 'asc' : 'desc');
        } elseif ($sort === 'year') {
            $query->orderBy('year', $direction);
        } elseif ($sort === 'artist') {
            $query->orderBy('artist', $direction);
        } elseif ($sort === 'title') {
            $query->orderBy('title', $direction);
        } else {
            $query->leftJoin('discogs_collection_items', 'discogs_releases.discogs_id', '=', 'discogs_collection_items.discogs_release_id')
                  ->orderBy('discogs_collection_items.date_added', $direction)
                  ->select('discogs_releases.*');
        }

        $releases = $query->paginate(48)->withQueryString();

        $allGenres = Genre::orderBy('name')->pluck('name');
        $allStyles = Style::orderBy('name')->pluck('name');

        return Inertia::render('Collection/Index', [
            'releases' => $releases,
            'filters' => $request->only(['search', 'genres', 'styles', 'sort', 'direction']),
            'allGenres' => $allGenres,
            'allStyles' => $allStyles,
            'username' => Setting::get('discogs_username'),
            'lastSynced' => Setting::get('collection_last_synced'),
        ]);
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

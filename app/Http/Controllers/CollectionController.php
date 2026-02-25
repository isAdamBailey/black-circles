<?php

namespace App\Http\Controllers;

use App\Models\DiscogsRelease;
use App\Models\Setting;
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
            foreach ((array) $genres as $genre) {
                $query->where('genres', 'like', "%{$genre}%");
            }
        }

        if ($styles = $request->get('styles')) {
            foreach ((array) $styles as $style) {
                $query->where('styles', 'like', "%{$style}%");
            }
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

        $allGenres = DiscogsRelease::whereNotNull('genres')
            ->pluck('genres')
            ->flatMap(fn($g) => is_array($g) ? $g : json_decode($g, true) ?? [])
            ->unique()->sort()->values();

        $allStyles = DiscogsRelease::whereNotNull('styles')
            ->pluck('styles')
            ->flatMap(fn($s) => is_array($s) ? $s : json_decode($s, true) ?? [])
            ->unique()->sort()->values();

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
            ->with('collectionItem')
            ->firstOrFail();

        $release = $discogs->enrichRelease($release);

        return Inertia::render('Collection/Show', [
            'release' => $release,
        ]);
    }
}

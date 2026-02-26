<?php

namespace App\Http\Controllers;

use App\Models\DiscogsRelease;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MoodController extends Controller
{
    private const MOODS = [
        'melancholy' => [
            'label' => 'Melancholy',
            'emoji' => '🌧',
            'genres' => ['Blues', 'Jazz', 'Folk, World, & Country'],
            'styles' => ['Slowcore', 'Soul', 'Acoustic', 'Ballad'],
        ],
        'energetic' => [
            'label' => 'Energetic',
            'emoji' => '⚡',
            'genres' => ['Rock', 'Electronic', 'Hip-Hop'],
            'styles' => ['Punk', 'Hardcore', 'Techno', 'Garage Rock'],
        ],
        'chill' => [
            'label' => 'Chill',
            'emoji' => '🌿',
            'genres' => ['Jazz', 'Electronic', 'Folk, World, & Country'],
            'styles' => ['Ambient', 'Downtempo', 'Bossa Nova', 'Lounge'],
        ],
        'dark' => [
            'label' => 'Dark',
            'emoji' => '🌑',
            'genres' => ['Rock', 'Electronic', 'Metal'],
            'styles' => ['Gothic Rock', 'Post-Punk', 'Industrial', 'Doom Metal', 'Darkwave'],
        ],
        'happy' => [
            'label' => 'Happy',
            'emoji' => '☀️',
            'genres' => ['Pop', 'Reggae', 'Funk / Soul'],
            'styles' => ['Disco', 'Funk', 'Pop Rock', 'Bubblegum'],
        ],
        'fast' => [
            'label' => 'Fast',
            'emoji' => '🔥',
            'genres' => [],
            'styles' => ['Speed Metal', 'Thrash Metal', 'Power Metal', 'Death Metal', 'Black Metal', 'Heavy Metal', 'Grindcore', 'Metalcore', 'Neoclassical', 'US Power Metal'],
            'exclude_styles' => ['Doom Metal', 'Stoner Rock'],
        ],
        'focus' => [
            'label' => 'Focus',
            'emoji' => '🎯',
            'genres' => ['Classical', 'Electronic', 'Jazz'],
            'styles' => ['Ambient', 'Post-Rock', 'Instrumental', 'Modern Classical'],
        ],
        'party' => [
            'label' => 'Party',
            'emoji' => '🎉',
            'genres' => ['Electronic', 'Hip-Hop', 'Funk / Soul'],
            'styles' => ['Punk', 'House', 'Techno', 'Disco', 'Funk', 'Dance'],
        ],
    ];

    public function index(): Response
    {
        $moods = collect(self::MOODS)->map(fn (array $m, string $slug) => [
            'slug' => $slug,
            'label' => $m['label'],
            'emoji' => $m['emoji'],
        ])->values();

        return Inertia::render('Home', [
            'moods' => $moods,
            'username' => Setting::discogsUsername(),
        ]);
    }

    public function suggest(string $mood): Response|RedirectResponse
    {
        $moodKey = strtolower($mood);
        if (! isset(self::MOODS[$moodKey])) {
            return redirect()->route('home');
        }

        $config = self::MOODS[$moodKey];
        $pool = $this->fetchMatchingReleases($config, 5);

        if ($pool->isEmpty()) {
            $pool = DiscogsRelease::query()
                ->whereHas('collectionItem')
                ->with(['genres', 'styles'])
                ->inRandomOrder()
                ->limit(5)
                ->get();
        }

        if ($pool->isEmpty()) {
            return redirect()->route('home')->with('error', 'Your collection is empty. Sync your Discogs collection to get suggestions.');
        }

        $primary = $pool->first();
        $backups = $pool->skip(1)->values()->map(fn ($r) => $this->formatRelease($r));
        $primaryFormatted = $this->formatRelease($primary);

        return Inertia::render('Mood/Suggest', [
            'mood' => [
                'slug' => $moodKey,
                'label' => $config['label'],
                'emoji' => $config['emoji'],
            ],
            'primary' => $primaryFormatted,
            'backups' => $backups,
        ]);
    }

    private function fetchMatchingReleases(array $config, int $limit): Collection
    {
        $genres = $config['genres'] ?? [];
        $styles = $config['styles'] ?? [];
        $excludeStyles = $config['exclude_styles'] ?? [];

        $query = DiscogsRelease::query()
            ->whereHas('collectionItem')
            ->where(function ($q) use ($genres, $styles) {
                if (! empty($genres)) {
                    $q->whereHas('genres', fn ($g) => $g->whereIn('name', $genres));
                }
                if (! empty($styles)) {
                    $q->orWhereHas('styles', fn ($s) => $s->whereIn('name', $styles));
                }
            });

        if (! empty($excludeStyles)) {
            $query->whereDoesntHave('styles', fn ($s) => $s->whereIn('name', $excludeStyles));
        }

        return $query
            ->with(['genres', 'styles'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    private function formatRelease(DiscogsRelease $release): array
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

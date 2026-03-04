<?php

namespace App\Services;

use App\Models\DiscogsRelease;
use Illuminate\Support\Facades\DB;

class PersonalityInsightService
{
    /**
     * Personality trait labels used for zero-shot classification.
     */
    private const TRAIT_LABELS = [
        'open to new experiences and adventurous',
        'introspective and reflective',
        'energetic and extroverted',
        'calm and introverted',
        'intellectually curious',
        'emotionally sensitive',
        'creative and artistic',
        'rebellious and unconventional',
        'nostalgic and sentimental',
        'intense and passionate',
        'playful and fun-loving',
        'sophisticated and refined',
    ];

    public function __construct(
        private HuggingFaceService $huggingFace
    ) {}

    /**
     * Return the top genres in the collection ordered by release count.
     *
     * @return array<array{name: string, count: int}>
     */
    public function topGenres(int $limit = 10): array
    {
        return DB::table('discogs_release_genre')
            ->join('genres', 'genres.id', '=', 'discogs_release_genre.genre_id')
            ->join('discogs_collection_items', 'discogs_collection_items.discogs_release_id', '=', 'discogs_release_genre.discogs_release_id')
            ->select('genres.name', DB::raw('COUNT(*) as count'))
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => (int) $row->count])
            ->all();
    }

    /**
     * Return the top styles in the collection ordered by release count.
     *
     * @return array<array{name: string, count: int}>
     */
    public function topStyles(int $limit = 10): array
    {
        return DB::table('discogs_release_style')
            ->join('styles', 'styles.id', '=', 'discogs_release_style.style_id')
            ->join('discogs_collection_items', 'discogs_collection_items.discogs_release_id', '=', 'discogs_release_style.discogs_release_id')
            ->select('styles.name', DB::raw('COUNT(*) as count'))
            ->groupBy('styles.id', 'styles.name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => (int) $row->count])
            ->all();
    }

    /**
     * Return the total number of releases in the collection.
     */
    public function collectionSize(): int
    {
        return DiscogsRelease::query()->whereHas('collectionItem')->count();
    }

    /**
     * Build a plain-text description of the collection for the AI model.
     */
    public function buildCollectionDescription(array $topGenres, array $topStyles): string
    {
        $genreNames = array_column($topGenres, 'name');
        $styleNames = array_column($topStyles, 'name');

        $parts = [];
        if (! empty($genreNames)) {
            $parts[] = 'Genres: '.implode(', ', $genreNames);
        }
        if (! empty($styleNames)) {
            $parts[] = 'Styles: '.implode(', ', $styleNames);
        }

        return implode('. ', $parts);
    }

    /**
     * Classify the collection description against personality trait labels.
     *
     * @return array<string, float> Trait label => score pairs, sorted by score desc
     */
    public function classifyTraits(string $description): array
    {
        if (empty($description)) {
            return [];
        }

        return $this->huggingFace->classifyText($description, self::TRAIT_LABELS);
    }

    /**
     * Return the list of all trait labels used for classification.
     *
     * @return array<string>
     */
    public function traitLabels(): array
    {
        return self::TRAIT_LABELS;
    }
}

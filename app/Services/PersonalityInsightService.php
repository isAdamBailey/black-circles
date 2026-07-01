<?php

namespace App\Services;

use App\Models\DiscogsRelease;
use Illuminate\Support\Facades\DB;

class PersonalityInsightService
{
    private const INSIGHT_MAX_NEW_TOKENS = 300;

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
            ->join('discogs_releases', 'discogs_releases.id', '=', 'discogs_release_genre.discogs_release_id')
            ->join('discogs_collection_items', 'discogs_collection_items.discogs_release_id', '=', 'discogs_releases.discogs_id')
            ->select('genres.name', DB::raw('COUNT(DISTINCT discogs_releases.id) as count'))
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
            ->join('discogs_releases', 'discogs_releases.id', '=', 'discogs_release_style.discogs_release_id')
            ->join('discogs_collection_items', 'discogs_collection_items.discogs_release_id', '=', 'discogs_releases.discogs_id')
            ->select('styles.name', DB::raw('COUNT(DISTINCT discogs_releases.id) as count'))
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
     * Build the AI prompt from the most-used styles (and optionally genres).
     * Returns an empty string when there are no styles or genres to describe.
     */
    public function buildPrompt(array $topStyles, array $topGenres = []): string
    {
        $styleNames = array_column($topStyles, 'name');
        $genreNames = array_column($topGenres, 'name');

        if (empty($styleNames) && empty($genreNames)) {
            return '';
        }

        $lines = [];
        if (! empty($styleNames)) {
            $lines[] = 'Most listened styles: '.implode(', ', $styleNames).'.';
        }
        if (! empty($genreNames)) {
            $lines[] = 'Genres: '.implode(', ', $genreNames).'.';
        }

        $musicDescription = implode(' ', $lines);

        return "A record collection is dominated by the following. {$musicDescription} "
            .'Based only on these musical preferences, write a short paragraph (3-4 sentences) summarizing '
            .'the overall vibe and character of this collection and what it suggests about the kind of listener '
            .'who owns it. Synthesize an overall impression rather than listing the genres or styles back out by name. '
            .'Do not use a specific name — refer to "this collection" or "this listener". '
            .'Keep it concise and make sure the paragraph is a complete thought that does not trail off. '
            .'Do not add any preamble.';
    }

    /**
     * Generate a free-text personality insight for the given top styles using the AI.
     * Returns an empty string if there are no styles, no token, or the API fails.
     */
    public function generatePersonalityInsight(array $topStyles, array $topGenres = []): string
    {
        $prompt = $this->buildPrompt($topStyles, $topGenres);
        if (empty($prompt)) {
            return '';
        }

        $insight = trim($this->huggingFace->generateText($prompt, self::INSIGHT_MAX_NEW_TOKENS));

        if ($insight === '') {
            return '';
        }

        return $this->trimToLastCompleteSentence($insight);
    }

    /**
     * Guards against a mid-sentence cutoff regardless of the model's token budget:
     * if the text doesn't end in terminal punctuation, drop the trailing incomplete sentence.
     */
    private function trimToLastCompleteSentence(string $text): string
    {
        if (preg_match('/[.!?]["\')]?$/', $text) === 1) {
            return $text;
        }

        $boundaries = array_filter([strrpos($text, '.'), strrpos($text, '!'), strrpos($text, '?')], fn ($pos) => $pos !== false);
        if ($boundaries === []) {
            return $text;
        }

        return trim(substr($text, 0, max($boundaries) + 1));
    }
}

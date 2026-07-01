<?php

namespace App\Services;

use App\Models\DiscogsRelease;
use App\Support\TextTokenizer;
use Illuminate\Support\Collection;

class ReleaseRanker
{
    private const GENRE_MATCH_WEIGHT = 2.0;

    private const STYLE_MATCH_WEIGHT = 1.5;

    private const ARTIST_MATCH_WEIGHT = 3.0;

    private const TITLE_MATCH_WEIGHT = 2.0;

    private const OVERLAP_WEIGHT = 0.25;

    private const TIER_SIZE = 0.5;

    /**
     * @param  Collection<int, DiscogsRelease>  $candidates
     * @return Collection<int, DiscogsRelease>
     */
    public function rank(string $prompt, Collection $candidates, int $want = 5): Collection
    {
        if ($candidates->isEmpty()) {
            return $candidates;
        }

        $tokens = TextTokenizer::tokenize($prompt);
        if ($tokens === []) {
            return $candidates->take($want)->values();
        }
        $tokenSet = array_flip($tokens);

        $scored = $candidates->map(fn (DiscogsRelease $release) => [
            'release' => $release,
            'score' => $this->score($tokenSet, $release),
        ]);

        $tiers = $scored
            ->groupBy(fn (array $row) => (string) (round($row['score'] / self::TIER_SIZE) * self::TIER_SIZE))
            ->sortByDesc(fn (Collection $rows, string $tier) => (float) $tier)
            ->map(fn (Collection $rows) => $rows->shuffle());

        return $tiers
            ->flatten(1)
            ->take($want)
            ->map(fn (array $row) => $row['release'])
            ->values();
    }

    /**
     * @param  array<string, int>  $tokenSet
     */
    private function score(array $tokenSet, DiscogsRelease $release): float
    {
        $score = 0.0;

        $genreNames = $release->relationLoaded('genres') ? $release->genres->pluck('name')->all() : [];
        $styleNames = $release->relationLoaded('styles') ? $release->styles->pluck('name')->all() : [];

        foreach ($genreNames as $name) {
            if ($this->hasWordOverlap($tokenSet, $name)) {
                $score += self::GENRE_MATCH_WEIGHT;
            }
        }

        foreach ($styleNames as $name) {
            if ($this->hasWordOverlap($tokenSet, $name)) {
                $score += self::STYLE_MATCH_WEIGHT;
            }
        }

        if ($this->hasWordOverlap($tokenSet, (string) $release->artist)) {
            $score += self::ARTIST_MATCH_WEIGHT;
        }

        if ($this->hasWordOverlap($tokenSet, (string) $release->title)) {
            $score += self::TITLE_MATCH_WEIGHT;
        }

        $bagOfWords = implode(' ', [$release->title, $release->artist, ...$genreNames, ...$styleNames]);
        $overlapCount = count(array_intersect(TextTokenizer::stemWords($bagOfWords), array_keys($tokenSet)));
        $score += self::OVERLAP_WEIGHT * min($overlapCount, 5);

        return $score;
    }

    /**
     * @param  array<string, int>  $tokenSet
     */
    private function hasWordOverlap(array $tokenSet, string $text): bool
    {
        foreach (TextTokenizer::stemWords($text) as $word) {
            if (isset($tokenSet[$word])) {
                return true;
            }
        }

        return false;
    }
}

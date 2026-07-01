<?php

namespace App\Services;

use App\Models\DiscogsRelease;
use App\Models\Genre;
use App\Models\Style;
use Illuminate\Support\Collection;

class VibeSuggestionService
{
    private const TAG_POOL_LIMIT = 40;

    private const SCOUT_POOL_LIMIT = 40;

    private const MERGED_CAP = 64;

    private const OUTPUT_COUNT = 5;

    public function __construct(
        private MoodKeywordMatcher $keywordMatcher,
        private ReleaseRanker $ranker,
        private ReleaseSuggestionService $releases
    ) {}

    /**
     * @param  array{slug: string, label: string, emoji: string}|null  $moodForUi
     * @param  array{genres: array<int, string>, styles: array<int, string>, exclude_styles: array<int, string>}|null  $moodPresetTags
     * @return array{mood: array<string, mixed>, primary: array<string, mixed>, backups: array<int, array<string, mixed>>}|null
     */
    public function buildSuggestion(string $prompt, ?array $moodForUi = null, ?array $moodPresetTags = null): ?array
    {
        $usePresetGate = is_array($moodPresetTags)
            && (! empty($moodPresetTags['genres']) || ! empty($moodPresetTags['styles']));

        if ($usePresetGate) {
            $presetPool = $this->releases->fetchMatchingReleases(
                $moodPresetTags['genres'],
                $moodPresetTags['styles'],
                $moodPresetTags['exclude_styles'] ?? [],
                self::MERGED_CAP
            );
            if ($presetPool->isNotEmpty()) {
                $ordered = $this->ranker->rank($prompt, $presetPool, self::OUTPUT_COUNT);

                return $this->composeSuggestionResponse($prompt, $ordered, $moodForUi);
            }
        }

        $allGenres = Genre::orderedNamesInCollection();
        $allStyles = Style::orderedNamesInCollection();

        $tagPool = collect();
        if (! empty($allGenres) || ! empty($allStyles)) {
            $partitioned = $this->keywordMatcher->matchTags($prompt, $allGenres, $allStyles);
            if (! empty($partitioned['genres']) || ! empty($partitioned['styles'])) {
                $tagPool = $this->releases->fetchMatchingReleases(
                    $partitioned['genres'],
                    $partitioned['styles'],
                    [],
                    self::TAG_POOL_LIMIT
                );
            }
        }

        $scoutPool = $this->releases->scoutCollectionReleases($prompt, self::SCOUT_POOL_LIMIT);

        $merged = $tagPool
            ->concat($scoutPool)
            ->unique('discogs_id')
            ->take(self::MERGED_CAP);

        if ($merged->count() < self::OUTPUT_COUNT) {
            $haveIds = $merged->pluck('discogs_id')->flip()->all();
            $extra = $this->releases->randomReleases(self::MERGED_CAP)
                ->reject(fn (DiscogsRelease $r) => isset($haveIds[$r->discogs_id]));
            $merged = $merged->concat($extra)->unique('discogs_id')->take(self::MERGED_CAP);
        }

        if ($merged->isEmpty()) {
            $merged = $this->releases->randomReleases(self::OUTPUT_COUNT);
        }

        if ($merged->isEmpty()) {
            return null;
        }

        $ordered = $this->ranker->rank($prompt, $merged, self::OUTPUT_COUNT);

        return $this->composeSuggestionResponse($prompt, $ordered, $moodForUi);
    }

    /**
     * @param  array{slug: string, label: string, emoji: string}|null  $moodForUi
     * @return array{mood: array<string, mixed>, primary: array<string, mixed>, backups: array<int, array<string, mixed>>}|null
     */
    private function composeSuggestionResponse(string $prompt, Collection $ordered, ?array $moodForUi): ?array
    {
        if ($ordered->isEmpty()) {
            return null;
        }

        $primary = $ordered->first();
        $backups = $ordered->skip(1)->take(self::OUTPUT_COUNT - 1)->values();

        $moodPayload = $moodForUi !== null
            ? [
                'slug' => $moodForUi['slug'],
                'label' => $moodForUi['label'],
                'emoji' => $moodForUi['emoji'],
                'vibePrompt' => $prompt,
            ]
            : [
                'slug' => 'vibe',
                'label' => $prompt,
                'emoji' => '🎵',
                'vibePrompt' => $prompt,
            ];

        return [
            'mood' => $moodPayload,
            'primary' => $this->releases->formatRelease($primary),
            'backups' => $backups->map(fn (DiscogsRelease $r) => $this->releases->formatRelease($r))->all(),
        ];
    }
}

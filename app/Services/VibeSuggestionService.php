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
        private HuggingFaceService $huggingFace,
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
                $ordered = $this->applyRerank($prompt, $presetPool);

                return $this->composeSuggestionResponse($prompt, $ordered, $moodForUi);
            }
        }

        $allGenres = Genre::orderedNamesInCollection();
        $allStyles = Style::orderedNamesInCollection();
        $labels = array_values(array_unique(array_merge($allGenres, $allStyles)));

        $tagPool = collect();
        if (! empty($labels)) {
            $scored = $this->huggingFace->classifyText($prompt, $labels);
            $partitioned = $this->huggingFace->partitionLabels($scored, $allGenres, $allStyles);
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

        $ordered = $this->applyRerank($prompt, $merged);

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

    /**
     * @param  Collection<int, DiscogsRelease>  $merged
     * @return Collection<int, DiscogsRelease>
     */
    private function applyRerank(string $prompt, Collection $merged): Collection
    {
        if ($merged->isEmpty()) {
            return $merged;
        }

        $numbered = [];
        $i = 1;
        foreach ($merged as $release) {
            $numbered[] = [
                'discogs_id' => (int) $release->discogs_id,
                'line' => $this->formatRerankLine($i, $release),
            ];
            $i++;
        }

        $ids = $this->huggingFace->rerankReleaseIdsForPrompt($prompt, $numbered, self::OUTPUT_COUNT);
        if ($ids === []) {
            return $merged->take(self::OUTPUT_COUNT)->values();
        }

        $byDiscogs = $merged->keyBy('discogs_id');
        $picked = collect();
        foreach ($ids as $discogsId) {
            $r = $byDiscogs->get($discogsId);
            if ($r) {
                $picked->push($r);
            }
            if ($picked->count() >= self::OUTPUT_COUNT) {
                break;
            }
        }

        $pickedIds = $picked->pluck('discogs_id')->all();
        foreach ($merged as $r) {
            if ($picked->count() >= self::OUTPUT_COUNT) {
                break;
            }
            if (! in_array($r->discogs_id, $pickedIds, true)) {
                $picked->push($r);
                $pickedIds[] = $r->discogs_id;
            }
        }

        return $picked->take(self::OUTPUT_COUNT)->values();
    }

    private function formatRerankLine(int $index, DiscogsRelease $release): string
    {
        $genres = $release->relationLoaded('genres')
            ? $release->genres->pluck('name')->implode(', ')
            : '';
        $styles = $release->relationLoaded('styles')
            ? $release->styles->pluck('name')->implode(', ')
            : '';
        $tags = trim(implode('; ', array_filter([$genres, $styles])));

        return sprintf(
            '%d. %d | %s — %s | %s',
            $index,
            $release->discogs_id,
            $release->artist ?? '',
            $release->title ?? '',
            $tags
        );
    }
}

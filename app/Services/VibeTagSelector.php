<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VibeTagSelector
{
    // The vibe endpoint is synchronous, so fail fast to the keyword matcher instead of retrying.
    private const TIMEOUT = 9.0;

    private const MAX_RETRIES = 0;

    private const MAX_TOKENS = 4000;

    private const CACHE_TTL_SECONDS = 86400;

    // After a failed call, skip Claude for a while so an outage doesn't add the timeout to every search.
    private const UNAVAILABLE_KEY = 'vibe-tags:claude-unavailable';

    private const UNAVAILABLE_SECONDS = 60;

    private const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'genres' => ['type' => 'array', 'items' => ['type' => 'string']],
            'styles' => ['type' => 'array', 'items' => ['type' => 'string']],
            'exclude_styles' => ['type' => 'array', 'items' => ['type' => 'string']],
        ],
        'required' => ['genres', 'styles', 'exclude_styles'],
        'additionalProperties' => false,
    ];

    public function __construct(
        private ClaudeService $claude,
        private MoodKeywordMatcher $keywordMatcher
    ) {}

    /**
     * Pick genres and styles from the collection's own lists that fit a free-text vibe.
     * Claude chooses them when available; otherwise (no key, error, timeout, or no usable tags)
     * this falls back to keyword matching.
     *
     * @param  array<string>  $allGenres  Genre names present in the user's collection
     * @param  array<string>  $allStyles  Style names present in the user's collection
     * @return array{genres: array<string>, styles: array<string>, exclude_styles: array<string>}
     */
    public function selectTags(string $prompt, array $allGenres, array $allStyles): array
    {
        $tags = $this->claudeTags($prompt, $allGenres, $allStyles);
        if ($tags !== null && (! empty($tags['genres']) || ! empty($tags['styles']))) {
            return $tags;
        }

        return [...$this->keywordMatcher->matchTags($prompt, $allGenres, $allStyles), 'exclude_styles' => []];
    }

    /**
     * Ask Claude for tags, dropping anything not in the lists. Returns null when Claude is unavailable or fails.
     *
     * @param  array<string>  $allGenres
     * @param  array<string>  $allStyles
     * @return array{genres: array<string>, styles: array<string>, exclude_styles: array<string>}|null
     */
    private function claudeTags(string $prompt, array $allGenres, array $allStyles): ?array
    {
        if (Cache::has(self::UNAVAILABLE_KEY)) {
            return null;
        }

        $cacheKey = $this->cacheKey($prompt, $allGenres, $allStyles);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $response = $this->claude->generateJson(
            $this->systemPrompt($allGenres, $allStyles),
            "<vibe>\n".trim($prompt)."\n</vibe>",
            self::SCHEMA,
            self::MAX_TOKENS,
            self::TIMEOUT,
            self::MAX_RETRIES
        );
        if ($response === null) {
            Cache::put(self::UNAVAILABLE_KEY, true, self::UNAVAILABLE_SECONDS);

            return null;
        }

        $tags = [
            'genres' => $this->onlyKnown($response['genres'] ?? [], $allGenres),
            'styles' => $this->onlyKnown($response['styles'] ?? [], $allStyles),
            'exclude_styles' => $this->onlyKnown($response['exclude_styles'] ?? [], $allStyles),
        ];
        $tags['exclude_styles'] = array_values(array_diff($tags['exclude_styles'], $tags['styles']));

        Cache::put($cacheKey, $tags, self::CACHE_TTL_SECONDS);

        return $tags;
    }

    /**
     * @param  array<string>  $allGenres
     * @param  array<string>  $allStyles
     */
    private function systemPrompt(array $allGenres, array $allStyles): string
    {
        return 'You help someone pick a record to play from their personal vinyl collection. '
            .'They describe a mood, setting or vibe in plain English, and you choose the genres and styles '
            .'from their collection that best fit it. The chosen tags are used to pull candidate records, '
            .'so prefer a focused selection of the strongest matches over everything loosely related. '
            .'Use exclude_styles only for styles that would clearly clash with the request; otherwise leave it empty. '
            .'Copy names exactly as they appear in the lists below, and only use names from these lists. '
            .'If nothing fits, return empty lists.'
            ."\n\n<genres>\n".implode("\n", $allGenres)."\n</genres>"
            ."\n\n<styles>\n".implode("\n", $allStyles)."\n</styles>";
    }

    /**
     * @param  array<mixed>  $names
     * @param  array<string>  $known
     * @return array<string>
     */
    private function onlyKnown(array $names, array $known): array
    {
        $names = array_filter($names, 'is_string');

        return array_values(array_unique(array_intersect($names, $known)));
    }

    /**
     * Keyed on the vocabulary too, so a sync that changes the collection's tags invalidates old choices.
     *
     * @param  array<string>  $allGenres
     * @param  array<string>  $allStyles
     */
    private function cacheKey(string $prompt, array $allGenres, array $allStyles): string
    {
        return 'vibe-tags:'.md5(Str::squish(mb_strtolower($prompt)).'|'.implode(',', $allGenres).'|'.implode(',', $allStyles));
    }
}

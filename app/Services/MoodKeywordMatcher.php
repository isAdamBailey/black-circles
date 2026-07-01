<?php

namespace App\Services;

use App\Support\TextTokenizer;

class MoodKeywordMatcher
{
    /**
     * @param  array<string>  $allGenres  Genre names present in the user's collection
     * @param  array<string>  $allStyles  Style names present in the user's collection
     * @return array{genres: array<string>, styles: array<string>}
     */
    public function matchTags(string $prompt, array $allGenres, array $allStyles): array
    {
        $tokens = TextTokenizer::tokenize($prompt);
        if ($tokens === []) {
            return ['genres' => [], 'styles' => []];
        }

        $genres = $this->directMatches($tokens, $allGenres);
        $styles = $this->directMatches($tokens, $allStyles);

        $expanded = $this->keywordExpansion($tokens);
        $genres = array_merge($genres, array_intersect($expanded['genres'], $allGenres));
        $styles = array_merge($styles, array_intersect($expanded['styles'], $allStyles));

        return [
            'genres' => array_values(array_unique($genres)),
            'styles' => array_values(array_unique($styles)),
        ];
    }

    /**
     * @param  array<string>  $tokens
     * @param  array<string>  $names
     * @return array<string>
     */
    private function directMatches(array $tokens, array $names): array
    {
        $tokenSet = array_flip($tokens);
        $matched = [];
        foreach ($names as $name) {
            foreach (TextTokenizer::stemWords($name) as $word) {
                if (isset($tokenSet[$word])) {
                    $matched[] = $name;
                    break;
                }
            }
        }

        return $matched;
    }

    /**
     * @param  array<string>  $tokens
     * @return array{genres: array<string>, styles: array<string>}
     */
    private function keywordExpansion(array $tokens): array
    {
        $dictionary = $this->normalizedDictionary();
        $genres = [];
        $styles = [];
        foreach ($tokens as $token) {
            if (! isset($dictionary[$token])) {
                continue;
            }
            $genres = array_merge($genres, $dictionary[$token]['genres'] ?? []);
            $styles = array_merge($styles, $dictionary[$token]['styles'] ?? []);
        }

        return ['genres' => $genres, 'styles' => $styles];
    }

    /**
     * Normalizes config keys through the same stemmer used on prompt tokens,
     * so lookups stay consistent regardless of the exact stemming rules.
     *
     * @return array<string, array{genres?: array<string>, styles?: array<string>}>
     */
    private function normalizedDictionary(): array
    {
        $raw = config('mood_keywords', []);
        $normalized = [];
        foreach ($raw as $keyword => $tags) {
            $normalized[TextTokenizer::stem(strtolower($keyword))] = $tags;
        }

        return $normalized;
    }
}

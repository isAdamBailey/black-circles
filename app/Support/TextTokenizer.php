<?php

namespace App\Support;

class TextTokenizer
{
    private const STOPWORDS = [
        'a', 'an', 'the', 'some', 'any', 'and', 'or', 'for', 'with', 'without',
        'me', 'my', 'i', 'to', 'of', 'in', 'on', 'at', 'is', 'be', 'that',
        'music', 'song', 'songs', 'record', 'records', 'album', 'albums',
        'vibe', 'vibes', 'mood', 'something', 'play', 'put',
    ];

    /**
     * @return array<string>
     */
    public static function tokenize(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? $text;
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = [];
        foreach ($words as $word) {
            if (in_array($word, self::STOPWORDS, true)) {
                continue;
            }
            $tokens[] = self::stem($word);
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Splits arbitrary text into lowercased, stemmed words (no stopword filtering).
     * Useful for normalizing names/titles for word-level comparison against tokenize() output.
     *
     * @return array<string>
     */
    public static function stemWords(string $text): array
    {
        $words = preg_split('/[^a-z0-9]+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_map(self::stem(...), $words);
    }

    public static function stem(string $word): string
    {
        foreach (['ing', 'ies', 'ish', 'ed', 'y', 's'] as $suffix) {
            $len = strlen($suffix);
            if (strlen($word) > $len + 2 && substr($word, -$len) === $suffix) {
                return substr($word, 0, -$len);
            }
        }

        return $word;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HuggingFaceService
{
    private const MODEL = 'facebook/bart-large-mnli';

    private const THRESHOLD = 0.15;

    private const MAX_LABELS = 50;

    private const MAX_TOP_LABELS = 10;

    private const TIMEOUT = 30;

    /**
     * Zero-shot classify text against candidate labels.
     * Returns labels with scores above threshold, sorted by score descending.
     *
     * @return array<string, float> Label => score pairs
     */
    public function classifyText(string $text, array $labels): array
    {
        $labels = array_values(array_unique(array_filter(array_map('trim', $labels))));
        if (empty($labels)) {
            return [];
        }

        if (count($labels) > self::MAX_LABELS) {
            $labels = array_slice($labels, 0, self::MAX_LABELS);
        }

        $token = config('services.huggingface.token');
        if (empty($token)) {
            return [];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(self::TIMEOUT)
                ->post('https://router.huggingface.co/hf-inference/models/'.self::MODEL, [
                    'inputs' => $text,
                    'parameters' => [
                        'candidate_labels' => $labels,
                        'multi_label' => true,
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('HuggingFace API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();
            $resultLabels = $data['labels'] ?? [];
            $scores = $data['scores'] ?? [];
            $pairs = [];
            foreach ($resultLabels as $i => $label) {
                $score = (float) ($scores[$i] ?? 0);
                if ($score >= self::THRESHOLD) {
                    $pairs[$label] = $score;
                }
            }
            arsort($pairs);

            return array_slice($pairs, 0, self::MAX_TOP_LABELS, true);
        } catch (\Throwable $e) {
            Log::warning('HuggingFace API exception', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Extract genre and style names from classifyText result.
     * Returns separate arrays of genres and styles based on what exists in the DB.
     *
     * @param  array<string, float>  $scoredLabels
     * @param  array<string>  $allGenres
     * @param  array<string>  $allStyles
     * @return array{genres: array<string>, styles: array<string>}
     */
    public function partitionLabels(array $scoredLabels, array $allGenres, array $allStyles): array
    {
        $genreSet = array_flip($allGenres);
        $styleSet = array_flip($allStyles);
        $genres = [];
        $styles = [];
        foreach ($scoredLabels as $label => $score) {
            if (isset($genreSet[$label])) {
                $genres[] = $label;
            }
            if (isset($styleSet[$label])) {
                $styles[] = $label;
            }
        }

        return ['genres' => $genres, 'styles' => $styles];
    }
}

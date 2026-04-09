<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HuggingFaceService
{
    private const MODEL = 'MoritzLaurer/deberta-v3-base-zeroshot-v2.0';

    private const TEXT_GEN_MODEL = 'Qwen/Qwen2.5-1.5B-Instruct';

    private const THRESHOLD = 0.15;

    private const MAX_TOP_LABELS = 10;

    private const MAX_NEW_TOKENS = 150;

    private const RERANK_MAX_NEW_TOKENS = 220;

    private const TIMEOUT = 90;

    private const CONNECT_TIMEOUT = 10;

    private const RETRY_TIMES = 3;

    private const RETRY_SLEEP_MS = 1000;

    private function inferenceClient(): PendingRequest
    {
        $token = config('services.huggingface.token');

        return Http::withToken($token)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout(self::TIMEOUT)
            ->retry(
                self::RETRY_TIMES,
                self::RETRY_SLEEP_MS,
                function ($exception): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }
                    if ($exception instanceof RequestException && $exception->response) {
                        return $exception->response->serverError();
                    }

                    return false;
                },
                false
            );
    }

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

        $token = config('services.huggingface.token');
        if (empty($token)) {
            return [];
        }

        try {
            $response = $this->inferenceClient()
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

    /**
     * @param  array<int, array{discogs_id: int, line: string}>  $numbered
     * @return array<int>
     */
    public function rerankReleaseIdsForPrompt(string $userPrompt, array $numbered, int $want = 5): array
    {
        if ($numbered === []) {
            return [];
        }

        $token = config('services.huggingface.token');
        if (empty($token)) {
            return [];
        }

        $lines = array_map(static fn (array $row): string => $row['line'], $numbered);
        $body = implode("\n", $lines);
        $encodedRequest = json_encode($userPrompt, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $instruction = <<<PROMPT
The listener asked for music matching this request: {$encodedRequest}

Below are albums from their collection. Pick the {$want} that best fit the request, best match first.
Reply with ONLY a comma-separated list of the numeric discogs_id values in order. No other words, labels, or punctuation beyond commas.

{$body}
PROMPT;

        $raw = trim($this->generateText($instruction, self::RERANK_MAX_NEW_TOKENS));
        if ($raw === '') {
            return [];
        }

        preg_match_all('/\d+/', $raw, $matches);
        $valid = array_map(static fn (array $row): int => (int) $row['discogs_id'], $numbered);
        $validSet = array_flip($valid);
        $ordered = [];
        foreach ($matches[0] as $digits) {
            $id = (int) $digits;
            if (isset($validSet[$id]) && ! in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
            if (count($ordered) >= $want) {
                break;
            }
        }

        return $ordered;
    }

    /**
     * Send a prompt to a text-generation model and return the generated string.
     * Returns an empty string on failure or when no token is configured.
     */
    public function generateText(string $prompt, ?int $maxNewTokens = null): string
    {
        $maxNewTokens = $maxNewTokens ?? self::MAX_NEW_TOKENS;
        $token = config('services.huggingface.token');
        if (empty($token)) {
            return '';
        }

        try {
            $response = $this->inferenceClient()
                ->post('https://router.huggingface.co/featherless-ai/v1/chat/completions', [
                    'model' => self::TEXT_GEN_MODEL,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => $maxNewTokens,
                ]);

            if (! $response->successful()) {
                Log::warning('HuggingFace text generation error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return '';
            }

            $data = $response->json();

            return trim($data['choices'][0]['message']['content'] ?? '');
        } catch (\Throwable $e) {
            Log::warning('HuggingFace text generation exception', ['message' => $e->getMessage()]);

            return '';
        }
    }
}

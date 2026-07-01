<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HuggingFaceService
{
    private const TEXT_GEN_MODEL = 'Qwen/Qwen2.5-1.5B-Instruct';

    private const MAX_NEW_TOKENS = 150;

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

<?php

namespace App\Services;

use Anthropic\Beta\AnthropicBeta;
use Anthropic\Beta\Messages\BetaMessage;
use Anthropic\Client;
use Anthropic\Messages\Model;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    private const MODEL = Model::CLAUDE_OPUS_5_5;

    private const EFFORT = 'low';

    public const DEFAULT_TIMEOUT = 60.0;

    private const DEFAULT_MAX_RETRIES = 2;

    public function __construct(
        private Container $container
    ) {}

    public function isConfigured(): bool
    {
        return ! empty(config('services.anthropic.key'));
    }

    /**
     * Send a prompt and return Claude's text reply.
     * Returns an empty string on failure, refusal or when no key is configured.
     */
    public function generateText(
        string $system,
        string $prompt,
        int $maxTokens,
        float $timeout = self::DEFAULT_TIMEOUT,
        int $maxRetries = self::DEFAULT_MAX_RETRIES
    ): string {
        return trim($this->send($system, $prompt, $maxTokens, $timeout, $maxRetries) ?? '');
    }

    /**
     * Send a prompt constrained to a JSON schema (structured outputs) and return the decoded object.
     * Returns null on failure, refusal, invalid JSON or when no key is configured.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>|null
     */
    public function generateJson(
        string $system,
        string $prompt,
        array $schema,
        int $maxTokens,
        float $timeout = self::DEFAULT_TIMEOUT,
        int $maxRetries = self::DEFAULT_MAX_RETRIES
    ): ?array {
        $format = ['type' => 'json_schema', 'schema' => $schema];
        $text = $this->send($system, $prompt, $maxTokens, $timeout, $maxRetries, $format);
        if ($text === null) {
            return null;
        }

        $data = json_decode($text, true);
        if (! is_array($data)) {
            Log::warning('Claude returned invalid JSON', ['body' => $text]);

            return null;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>|null  $format
     */
    private function send(
        string $system,
        string $prompt,
        int $maxTokens,
        float $timeout,
        int $maxRetries,
        ?array $format = null
    ): ?string {
        if (! $this->isConfigured()) {
            return null;
        }

        $outputConfig = ['effort' => self::EFFORT];
        if ($format !== null) {
            $outputConfig['format'] = $format;
        }

        try {
            /** @var Client $client */
            $client = $this->container->make(Client::class, ['timeout' => $timeout]);

            // fallbacks "default" re-runs a safety-classifier refusal on Anthropic's recommended fallback model.
            $message = $client->beta->messages->create(
                maxTokens: $maxTokens,
                messages: [['role' => 'user', 'content' => $prompt]],
                model: self::MODEL,
                system: $system,
                outputConfig: $outputConfig,
                fallbacks: 'default',
                betas: [AnthropicBeta::SERVER_SIDE_FALLBACK_2026_07_01],
                requestOptions: ['maxRetries' => $maxRetries],
            );
        } catch (\Throwable $e) {
            Log::warning('Claude request failed', ['exception' => $e::class, 'message' => $e->getMessage()]);

            return null;
        }

        if ($message->stopReason !== 'end_turn') {
            Log::warning('Claude response incomplete', [
                'stop_reason' => $message->stopReason,
                'refusal_category' => $message->stopDetails?->category,
            ]);

            return null;
        }

        return $this->textOf($message);
    }

    private function textOf(BetaMessage $message): string
    {
        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return $text;
    }
}

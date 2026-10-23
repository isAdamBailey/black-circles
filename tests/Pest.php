<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/**
 * A successful Messages API response whose only content is $text.
 */
function claudeMessage(string $text, string $stopReason = 'end_turn'): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode([
        'id' => 'msg_test',
        'type' => 'message',
        'role' => 'assistant',
        'model' => 'claude-opus-5-5',
        'content' => $text === '' ? [] : [['type' => 'text', 'text' => $text]],
        'stop_reason' => $stopReason,
        'stop_sequence' => null,
        'usage' => ['input_tokens' => 10, 'output_tokens' => 10],
    ]));
}

/**
 * An Anthropic API error response.
 */
function claudeError(int $status = 400): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode([
        'type' => 'error',
        'error' => ['type' => 'invalid_request_error', 'message' => 'Bad request'],
    ]));
}

/**
 * A transport failure, as Guzzle raises when the request times out.
 */
function claudeTimeout(): ConnectException
{
    return new ConnectException('Operation timed out', new Request('POST', 'https://api.anthropic.com/v1/messages'));
}

/**
 * The decoded JSON body of the first recorded Claude request.
 *
 * @param  ArrayObject<int, array{request: RequestInterface}>  $history
 * @return array<string, mixed>
 */
function claudeRequestBody(ArrayObject $history): array
{
    return json_decode((string) $history[0]['request']->getBody(), true);
}

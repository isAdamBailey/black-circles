<?php

namespace Tests;

use Anthropic\Client;
use Database\Seeders\MoodSeeder;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([MoodSeeder::class]);

        // Never reach the real Anthropic API: an empty mock queue fails any request that isn't faked.
        $this->fakeClaude([]);
    }

    /**
     * Route Claude requests through a Guzzle mock that replays $responses in order.
     * Returns the request history so tests can inspect what was sent.
     *
     * @param  array<ResponseInterface|\Throwable>  $responses
     * @return \ArrayObject<int, array{request: RequestInterface}>
     */
    protected function fakeClaude(array $responses): \ArrayObject
    {
        $history = new \ArrayObject;
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));

        $client = new Client(
            apiKey: 'test-key',
            requestOptions: ['transporter' => new GuzzleClient(['handler' => $stack])],
        );
        $this->app->bind(Client::class, fn () => $client);

        return $history;
    }
}

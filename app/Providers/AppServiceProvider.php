<?php

namespace App\Providers;

use Anthropic\Client;
use App\Services\ClaudeService;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The SDK leaves timeouts to the HTTP transport, so each client gets its own Guzzle
        // instance. Tests rebind Client::class to a fake so the real API is never called.
        $this->app->bind(Client::class, fn ($app, array $params) => new Client(
            apiKey: (string) config('services.anthropic.key'),
            requestOptions: [
                'transporter' => new GuzzleClient([
                    'connect_timeout' => 5,
                    'timeout' => $params['timeout'] ?? ClaudeService::DEFAULT_TIMEOUT,
                ]),
            ],
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

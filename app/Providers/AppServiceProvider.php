<?php

namespace App\Providers;

use App\Services\ClaudeService;
use GuzzleHttp\HandlerStack;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Tests swap this for a Guzzle mock so the real Anthropic API is never called.
        $this->app->when(ClaudeService::class)
            ->needs(HandlerStack::class)
            ->give(fn () => HandlerStack::create());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Vibe search is public and each new prompt is a paid Claude call, so cap it per client.
        RateLimiter::for('vibe', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
    }
}

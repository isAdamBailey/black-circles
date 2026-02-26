<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.huggingface.token' => 'test-token']);
});

it('returns suggestions when prompt matches collection genres and styles', function () {
    Http::fake([
        'router.huggingface.co/*' => Http::response([
            'labels' => ['Post-Punk', 'Gothic Rock', 'Rock'],
            'scores' => [0.9, 0.85, 0.7],
        ], 200),
    ]);

    $release = DiscogsRelease::factory()
        ->withGenres(['Rock'])
        ->withStyles(['Post-Punk', 'Gothic Rock'])
        ->create(['title' => 'Test Album', 'artist' => 'Test Artist']);
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $response = $this->post(route('vibe.suggest'), ['prompt' => 'dark moody post-punk']);

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Mood/Suggest')
            ->has('mood')
            ->has('primary')
            ->where('primary.title', 'Test Album')
            ->where('primary.artist', 'Test Artist')
        );
});

it('redirects to home when prompt is missing', function () {
    $response = $this->post(route('vibe.suggest'), []);

    $response->assertSessionHasErrors('prompt');
});

it('redirects to home when prompt is too short', function () {
    $response = $this->post(route('vibe.suggest'), ['prompt' => 'ab']);

    $response->assertSessionHasErrors('prompt');
});

it('falls back to random releases when API fails', function () {
    Http::fake([
        'router.huggingface.co/*' => Http::response([], 503),
    ]);

    $release = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $response = $this->post(route('vibe.suggest'), ['prompt' => 'something chill']);

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Mood/Suggest')
            ->has('primary')
        );
});

it('redirects to home with error when API token is missing', function () {
    config(['services.huggingface.token' => '']);

    $response = $this->post(route('vibe.suggest'), ['prompt' => 'dark jazz']);

    $response->assertRedirect(route('home'))
        ->assertSessionHas('error');
});

it('redirects to home when collection is empty', function () {
    Http::fake([
        'router.huggingface.co/*' => Http::response([
            'labels' => ['Jazz'],
            'scores' => [0.9],
        ], 200),
    ]);

    $response = $this->post(route('vibe.suggest'), ['prompt' => 'smooth jazz']);

    $response->assertRedirect(route('home'))
        ->assertSessionHas('error');
});


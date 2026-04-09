<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.huggingface.token' => 'test-token']);
});

it('returns suggestions when prompt matches collection genres and styles', function () {
    $release = DiscogsRelease::factory()
        ->withGenres(['Rock'])
        ->withStyles(['Post-Punk', 'Gothic Rock'])
        ->create(['title' => 'Test Album', 'artist' => 'Test Artist']);
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    Http::fake([
        'https://router.huggingface.co/hf-inference/*' => Http::response([
            'labels' => ['Post-Punk', 'Gothic Rock', 'Rock'],
            'scores' => [0.9, 0.85, 0.7],
        ], 200),
        'https://router.huggingface.co/featherless-ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => (string) $release->discogs_id]],
            ],
        ], 200),
    ]);

    $post = $this->post(route('vibe.suggest'), ['prompt' => 'dark moody post-punk']);
    $post->assertRedirect();
    $wait = $this->get($post->headers->get('Location'));
    $wait->assertRedirect();
    $result = $this->get($wait->headers->get('Location'));

    $result->assertStatus(200)
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
        'https://router.huggingface.co/*' => Http::response([], 503),
    ]);

    $release = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $post = $this->post(route('vibe.suggest'), ['prompt' => 'something chill']);
    $post->assertRedirect();
    $wait = $this->get($post->headers->get('Location'));
    $wait->assertRedirect();
    $result = $this->get($wait->headers->get('Location'));

    $result->assertStatus(200)
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

it('redirects GET /vibe to home', function () {
    $this->get(route('vibe.suggest.get'))->assertRedirect(route('home'));
});

it('redirects to home when collection is empty', function () {
    $post = $this->post(route('vibe.suggest'), ['prompt' => 'smooth jazz']);
    $post->assertRedirect();
    $wait = $this->get($post->headers->get('Location'));

    $wait->assertRedirect(route('home'))
        ->assertSessionHas('error');
});

it('poll returns pending with cache_miss when cache entry is missing', function () {
    $response = $this->getJson(route('vibe.poll', 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'));

    $response->assertOk();
    $data = $response->json();
    expect($data['ready'])->toBeFalse()
        ->and($data['error'])->toBeNull()
        ->and($data['cache_miss'] ?? null)->toBeTrue();
});

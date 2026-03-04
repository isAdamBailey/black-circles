<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use Illuminate\Support\Facades\Http;

it('renders the personality page', function () {
    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Personality/Show'));
});

it('returns top genres and styles from collection', function () {
    $release = DiscogsRelease::factory()
        ->withGenres(['Rock'])
        ->withStyles(['Post-Punk'])
        ->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Personality/Show')
            ->where('collectionSize', 1)
            ->has('topGenres', 1)
            ->where('topGenres.0.name', 'Rock')
            ->has('topStyles', 1)
            ->where('topStyles.0.name', 'Post-Punk')
        );
});

it('returns personality traits from AI when token is set', function () {
    config(['services.huggingface.token' => 'test-token']);

    Http::fake([
        'router.huggingface.co/*' => Http::response([
            'labels' => ['creative and artistic', 'introspective and reflective'],
            'scores' => [0.88, 0.72],
        ], 200),
    ]);

    $release = DiscogsRelease::factory()
        ->withGenres(['Rock'])
        ->withStyles(['Post-Punk'])
        ->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Personality/Show')
            ->has('traits')
            ->where('hasToken', true)
        );
});

it('returns empty traits when no huggingface token is configured', function () {
    config(['services.huggingface.token' => '']);

    $release = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Personality/Show')
            ->where('traits', [])
            ->where('hasToken', false)
        );
});

it('returns empty traits when collection is empty', function () {
    config(['services.huggingface.token' => 'test-token']);

    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Personality/Show')
            ->where('traits', [])
            ->where('collectionSize', 0)
        );
});

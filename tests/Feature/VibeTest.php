<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;

it('returns suggestions synchronously when prompt matches collection genres and styles', function () {
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

it('falls back to random releases when nothing in the prompt matches', function () {
    $release = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $response = $this->post(route('vibe.suggest'), ['prompt' => 'xyz completely unrelated gibberish']);

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Mood/Suggest')
            ->has('primary')
        );
});

it('redirects GET /vibe to home', function () {
    $this->get(route('vibe.suggest.get'))->assertRedirect(route('home'));
});

it('redirects to home when collection is empty', function () {
    $this->post(route('vibe.suggest'), ['prompt' => 'smooth jazz'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');
});

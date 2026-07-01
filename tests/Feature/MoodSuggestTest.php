<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;

it('renders preset mood suggestions synchronously', function () {
    $release = DiscogsRelease::factory()
        ->withGenres(['Jazz'])
        ->withStyles(['Ambient'])
        ->create(['title' => 'Chill Album', 'artist' => 'Chill Artist']);
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->get(route('mood.suggest', 'chill'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Mood/Suggest')
            ->where('mood.slug', 'chill')
            ->where('primary.title', 'Chill Album')
        );
});

it('renders preset mood suggestions the same way regardless of huggingface token config', function () {
    config(['services.huggingface.token' => 'test-token']);

    $release = DiscogsRelease::factory()
        ->withGenres(['Jazz'])
        ->withStyles(['Ambient'])
        ->create(['title' => 'Chill Album', 'artist' => 'Chill Artist']);
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->get(route('mood.suggest', 'chill'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Mood/Suggest')
            ->where('mood.slug', 'chill')
        );
});

it('redirects home with an error when the collection is empty', function () {
    $this->get(route('mood.suggest', 'chill'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');
});

it('redirects unknown mood slugs home', function () {
    $this->get(route('mood.suggest', 'not-a-real-mood'))
        ->assertRedirect(route('home'));
});

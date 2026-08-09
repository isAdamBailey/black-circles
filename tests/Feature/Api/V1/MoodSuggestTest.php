<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;

it('returns preset mood suggestions as json', function () {
    $release = DiscogsRelease::factory()
        ->withGenres(['Jazz'])
        ->withStyles(['Ambient'])
        ->create(['title' => 'Chill Album', 'artist' => 'Chill Artist']);
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->getJson(route('api.moods.suggest', 'chill'))
        ->assertStatus(200)
        ->assertJsonPath('data.mood.slug', 'chill')
        ->assertJsonPath('data.primary.title', 'Chill Album');
});

it('returns mood suggestions the same way regardless of huggingface token config', function () {
    config(['services.huggingface.token' => 'test-token']);

    $release = DiscogsRelease::factory()
        ->withGenres(['Jazz'])
        ->withStyles(['Ambient'])
        ->create(['title' => 'Chill Album', 'artist' => 'Chill Artist']);
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->getJson(route('api.moods.suggest', 'chill'))
        ->assertStatus(200)
        ->assertJsonPath('data.mood.slug', 'chill');
});

it('returns a 422 when the collection is empty', function () {
    $this->getJson(route('api.moods.suggest', 'chill'))
        ->assertStatus(422)
        ->assertJsonStructure(['message']);
});

it('returns a 404 for unknown mood slugs', function () {
    $this->getJson(route('api.moods.suggest', 'not-a-real-mood'))
        ->assertStatus(404)
        ->assertJsonStructure(['message']);
});

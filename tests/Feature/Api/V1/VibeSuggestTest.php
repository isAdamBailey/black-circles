<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;

it('returns suggestions as json when prompt matches collection genres and styles', function () {
    $release = DiscogsRelease::factory()
        ->withGenres(['Rock'])
        ->withStyles(['Post-Punk', 'Gothic Rock'])
        ->create(['title' => 'Test Album', 'artist' => 'Test Artist']);
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->postJson(route('api.vibe.suggest'), ['prompt' => 'dark moody post-punk'])
        ->assertStatus(200)
        ->assertJsonStructure(['data' => ['mood', 'primary', 'backups']])
        ->assertJsonPath('data.primary.title', 'Test Album')
        ->assertJsonPath('data.primary.artist', 'Test Artist');
});

it('returns a 422 with validation errors when prompt is missing', function () {
    $this->postJson(route('api.vibe.suggest'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('prompt');
});

it('returns a 422 with validation errors when prompt is too short', function () {
    $this->postJson(route('api.vibe.suggest'), ['prompt' => 'ab'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('prompt');
});

it('falls back to random releases when nothing in the prompt matches', function () {
    $release = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->postJson(route('api.vibe.suggest'), ['prompt' => 'xyz completely unrelated gibberish'])
        ->assertStatus(200)
        ->assertJsonStructure(['data' => ['primary']]);
});

it('returns a 422 when the collection is empty', function () {
    $this->postJson(route('api.vibe.suggest'), ['prompt' => 'smooth jazz'])
        ->assertStatus(422)
        ->assertJsonStructure(['message']);
});

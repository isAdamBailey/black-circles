<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;

it('redirects preset moods through the ai wait flow when huggingface is configured', function () {
    config(['services.huggingface.token' => 'test-token']);

    $response = $this->get(route('mood.suggest', 'chill'));
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/vibe/wait/');
});

it('renders preset mood suggestions from sql when huggingface is not configured', function () {
    config(['services.huggingface.token' => '']);

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

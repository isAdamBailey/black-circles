<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Models\Setting;

it('renders the collection index page', function () {
    $this->get(route('collection.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Collection/Index'));
});

it('shows releases on the collection index', function () {
    $release = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->get(route('collection.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 1)
        );
});

it('renders a release detail page', function () {
    $release = DiscogsRelease::factory()->create([
        'release_data_cached_at' => now(),
    ]);

    $this->get(route('collection.show', $release->discogs_id))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Collection/Show'));
});

it('filters releases by genre', function () {
    DiscogsRelease::factory()->withGenres(['Rock'])->create();
    DiscogsRelease::factory()->withGenres(['Electronic'])->create();

    $this->get(route('collection.index', ['genres' => 'Rock']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 1)
        );
});

it('searches releases by artist name', function () {
    DiscogsRelease::factory()->create(['title' => 'Dark Side of the Moon', 'artist' => 'Pink Floyd']);
    DiscogsRelease::factory()->create(['title' => 'Rumours', 'artist' => 'Fleetwood Mac']);

    $this->get(route('collection.index', ['search' => 'Pink Floyd']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 1)
        );
});

it('renders the settings page', function () {
    $this->get(route('settings.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Settings/Index'));
});

it('saves the discogs username in settings', function () {
    $this->post(route('settings.update'), ['discogs_username' => 'testuser'])
        ->assertRedirect();

    expect(Setting::get('discogs_username'))->toBe('testuser');
});

it('requires a discogs username when saving settings', function () {
    $this->post(route('settings.update'), ['discogs_username' => ''])
        ->assertSessionHasErrors('discogs_username');
});

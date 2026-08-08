<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;

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
    $rock = DiscogsRelease::factory()->withGenres(['Rock'])->create();
    $electronic = DiscogsRelease::factory()->withGenres(['Electronic'])->create();
    DiscogsCollectionItem::factory()->for($rock, 'release')->create();
    DiscogsCollectionItem::factory()->for($electronic, 'release')->create();

    $this->get(route('collection.index', ['genres' => 'Rock']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 1)
        );
});

it('searches releases by artist name', function () {
    $pf = DiscogsRelease::factory()->create(['title' => 'Dark Side of the Moon', 'artist' => 'Pink Floyd']);
    $fm = DiscogsRelease::factory()->create(['title' => 'Rumours', 'artist' => 'Fleetwood Mac']);
    DiscogsCollectionItem::factory()->for($pf, 'release')->create();
    DiscogsCollectionItem::factory()->for($fm, 'release')->create();

    $this->get(route('collection.index', ['search' => 'Pink Floyd']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 1)
        );
});

it('sorts collection by value using lowest_price', function () {
    $cheap = DiscogsRelease::factory()->create(['lowest_price' => 5.00]);
    $expensive = DiscogsRelease::factory()->create(['lowest_price' => 50.00]);
    $noPrice = DiscogsRelease::factory()->create(['lowest_price' => null]);
    DiscogsCollectionItem::factory()->for($cheap, 'release')->create();
    DiscogsCollectionItem::factory()->for($expensive, 'release')->create();
    DiscogsCollectionItem::factory()->for($noPrice, 'release')->create();

    $response = $this->get(route('collection.index', ['sort' => 'value', 'direction' => 'asc']));
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 3)
        );

    $data = $response->inertiaProps('releases.data');
    $orderedByPrice = collect($data)->map(fn ($r) => $r['lowest_price'])->all();
    expect((float) $orderedByPrice[0])->toBe(5.0)
        ->and((float) $orderedByPrice[1])->toBe(50.0)
        ->and($orderedByPrice[2])->toBeNull();
});

it('returns search results for lookahead', function () {
    config(['scout.driver' => 'database']);
    $release = DiscogsRelease::factory()->create(['title' => 'Dark Side', 'artist' => 'Pink Floyd']);
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $response = $this->getJson(route('collection.search', ['q' => 'Pink']));

    $response->assertStatus(200)
        ->assertJsonPath('data.0.title', 'Dark Side')
        ->assertJsonPath('data.0.artist', 'Pink Floyd');
});

it('provides releases as scrollable paginated data', function () {
    DiscogsRelease::factory()->count(3)->create()->each(function ($r) {
        DiscogsCollectionItem::factory()->for($r, 'release')->create();
    });

    $response = $this->get(route('collection.index'));
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 3)
            ->has('releases.links')
        );
});

it('filters releases by style', function () {
    $postPunk = DiscogsRelease::factory()->withStyles(['Post-Punk'])->create();
    $ambient = DiscogsRelease::factory()->withStyles(['Ambient'])->create();
    DiscogsCollectionItem::factory()->for($postPunk, 'release')->create();
    DiscogsCollectionItem::factory()->for($ambient, 'release')->create();

    $this->get(route('collection.index', ['styles' => 'Post-Punk']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 1)
        );
});

it('renders the empty state when the collection has no results', function () {
    $this->get(route('collection.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 0)
        );
});

it('sorts collection by year', function () {
    $older = DiscogsRelease::factory()->create(['year' => 1975]);
    $newer = DiscogsRelease::factory()->create(['year' => 2020]);
    DiscogsCollectionItem::factory()->for($older, 'release')->create();
    DiscogsCollectionItem::factory()->for($newer, 'release')->create();

    $response = $this->get(route('collection.index', ['sort' => 'year', 'direction' => 'asc']));
    $data = $response->inertiaProps('releases.data');

    expect(collect($data)->pluck('year')->all())->toBe([1975, 2020]);
});

it('sorts collection by artist', function () {
    $b = DiscogsRelease::factory()->create(['artist' => 'Bravo']);
    $a = DiscogsRelease::factory()->create(['artist' => 'Alpha']);
    DiscogsCollectionItem::factory()->for($b, 'release')->create();
    DiscogsCollectionItem::factory()->for($a, 'release')->create();

    $response = $this->get(route('collection.index', ['sort' => 'artist', 'direction' => 'asc']));
    $data = $response->inertiaProps('releases.data');

    expect(collect($data)->pluck('artist')->all())->toBe(['Alpha', 'Bravo']);
});

it('sorts collection by title', function () {
    $b = DiscogsRelease::factory()->create(['title' => 'Beta Album']);
    $a = DiscogsRelease::factory()->create(['title' => 'Alpha Album']);
    DiscogsCollectionItem::factory()->for($b, 'release')->create();
    DiscogsCollectionItem::factory()->for($a, 'release')->create();

    $response = $this->get(route('collection.index', ['sort' => 'title', 'direction' => 'asc']));
    $data = $response->inertiaProps('releases.data');

    expect(collect($data)->pluck('title')->all())->toBe(['Alpha Album', 'Beta Album']);
});

it('sorts collection by date_added', function () {
    $older = DiscogsRelease::factory()->create();
    $newer = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($older, 'release')->create(['date_added' => now()->subYear()]);
    DiscogsCollectionItem::factory()->for($newer, 'release')->create(['date_added' => now()]);

    $response = $this->get(route('collection.index', ['sort' => 'date_added', 'direction' => 'asc']));
    $data = $response->inertiaProps('releases.data');

    expect(collect($data)->pluck('discogs_id')->all())->toBe([
        $older->discogs_id,
        $newer->discogs_id,
    ]);
});

it('redirects home with an error when getting a random release from an empty collection', function () {
    $this->get(route('collection.random'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');
});

it('redirects to a release detail page for a random release', function () {
    $release = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->get(route('collection.random'))
        ->assertRedirect(route('collection.show', $release->discogs_id));
});

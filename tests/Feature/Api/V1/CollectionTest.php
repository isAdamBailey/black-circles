<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;

it('returns the collection index as paginated json', function () {
    $this->getJson(route('api.collection.index'))
        ->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'filters',
            'allGenres',
            'allStyles',
            'username',
            'lastSynced',
        ]);
});

it('shows releases on the collection index', function () {
    $release = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->getJson(route('api.collection.index'))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('returns a release detail as json', function () {
    $release = DiscogsRelease::factory()->create([
        'release_data_cached_at' => now(),
    ]);
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->getJson(route('api.collection.show', $release->discogs_id))
        ->assertStatus(200)
        ->assertJsonPath('data.discogs_id', $release->discogs_id)
        ->assertJsonPath('data.title', $release->title);
});

it('returns a 404 for an unknown release', function () {
    $this->getJson(route('api.collection.show', 999999))
        ->assertStatus(404);
});

it('filters releases by genre', function () {
    $rock = DiscogsRelease::factory()->withGenres(['Rock'])->create();
    $electronic = DiscogsRelease::factory()->withGenres(['Electronic'])->create();
    DiscogsCollectionItem::factory()->for($rock, 'release')->create();
    DiscogsCollectionItem::factory()->for($electronic, 'release')->create();

    $this->getJson(route('api.collection.index', ['genres' => 'Rock']))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('filters releases by style', function () {
    $postPunk = DiscogsRelease::factory()->withStyles(['Post-Punk'])->create();
    $ambient = DiscogsRelease::factory()->withStyles(['Ambient'])->create();
    DiscogsCollectionItem::factory()->for($postPunk, 'release')->create();
    DiscogsCollectionItem::factory()->for($ambient, 'release')->create();

    $this->getJson(route('api.collection.index', ['styles' => 'Post-Punk']))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('searches releases by artist name', function () {
    $pf = DiscogsRelease::factory()->create(['title' => 'Dark Side of the Moon', 'artist' => 'Pink Floyd']);
    $fm = DiscogsRelease::factory()->create(['title' => 'Rumours', 'artist' => 'Fleetwood Mac']);
    DiscogsCollectionItem::factory()->for($pf, 'release')->create();
    DiscogsCollectionItem::factory()->for($fm, 'release')->create();

    $this->getJson(route('api.collection.index', ['search' => 'Pink Floyd']))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('sorts collection by value using lowest_price', function () {
    $cheap = DiscogsRelease::factory()->create(['lowest_price' => 5.00]);
    $expensive = DiscogsRelease::factory()->create(['lowest_price' => 50.00]);
    $noPrice = DiscogsRelease::factory()->create(['lowest_price' => null]);
    DiscogsCollectionItem::factory()->for($cheap, 'release')->create();
    DiscogsCollectionItem::factory()->for($expensive, 'release')->create();
    DiscogsCollectionItem::factory()->for($noPrice, 'release')->create();

    $response = $this->getJson(route('api.collection.index', ['sort' => 'value', 'direction' => 'asc']));
    $response->assertStatus(200)->assertJsonCount(3, 'data');

    $prices = collect($response->json('data'))->pluck('lowest_price')->all();
    expect((float) $prices[0])->toBe(5.0)
        ->and((float) $prices[1])->toBe(50.0)
        ->and($prices[2])->toBeNull();
});

it('sorts collection by year', function () {
    $older = DiscogsRelease::factory()->create(['year' => 1975]);
    $newer = DiscogsRelease::factory()->create(['year' => 2020]);
    DiscogsCollectionItem::factory()->for($older, 'release')->create();
    DiscogsCollectionItem::factory()->for($newer, 'release')->create();

    $response = $this->getJson(route('api.collection.index', ['sort' => 'year', 'direction' => 'asc']));

    expect(collect($response->json('data'))->pluck('year')->all())->toBe([1975, 2020]);
});

it('sorts collection by artist', function () {
    $b = DiscogsRelease::factory()->create(['artist' => 'Bravo']);
    $a = DiscogsRelease::factory()->create(['artist' => 'Alpha']);
    DiscogsCollectionItem::factory()->for($b, 'release')->create();
    DiscogsCollectionItem::factory()->for($a, 'release')->create();

    $response = $this->getJson(route('api.collection.index', ['sort' => 'artist', 'direction' => 'asc']));

    expect(collect($response->json('data'))->pluck('artist')->all())->toBe(['Alpha', 'Bravo']);
});

it('sorts collection by title', function () {
    $b = DiscogsRelease::factory()->create(['title' => 'Beta Album']);
    $a = DiscogsRelease::factory()->create(['title' => 'Alpha Album']);
    DiscogsCollectionItem::factory()->for($b, 'release')->create();
    DiscogsCollectionItem::factory()->for($a, 'release')->create();

    $response = $this->getJson(route('api.collection.index', ['sort' => 'title', 'direction' => 'asc']));

    expect(collect($response->json('data'))->pluck('title')->all())->toBe(['Alpha Album', 'Beta Album']);
});

it('sorts collection by date_added', function () {
    $older = DiscogsRelease::factory()->create();
    $newer = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($older, 'release')->create(['date_added' => now()->subYear()]);
    DiscogsCollectionItem::factory()->for($newer, 'release')->create(['date_added' => now()]);

    $response = $this->getJson(route('api.collection.index', ['sort' => 'date_added', 'direction' => 'asc']));

    expect(collect($response->json('data'))->pluck('discogs_id')->all())->toBe([
        $older->discogs_id,
        $newer->discogs_id,
    ]);
});

it('renders the empty state when the collection has no results', function () {
    $this->getJson(route('api.collection.index'))
        ->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

it('returns search results for lookahead', function () {
    config(['scout.driver' => 'database']);
    $release = DiscogsRelease::factory()->create(['title' => 'Dark Side', 'artist' => 'Pink Floyd']);
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->getJson(route('api.collection.search', ['q' => 'Pink']))
        ->assertStatus(200)
        ->assertJsonPath('data.0.title', 'Dark Side')
        ->assertJsonPath('data.0.artist', 'Pink Floyd');
});

it('returns a 404 when getting a random release from an empty collection', function () {
    $this->getJson(route('api.collection.random'))
        ->assertStatus(404)
        ->assertJsonStructure(['message']);
});

it('returns a random release as json', function () {
    $release = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->getJson(route('api.collection.random'))
        ->assertStatus(200)
        ->assertJsonPath('data.discogs_id', $release->discogs_id);
});

<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Services\ReleaseRanker;

it('ranks a release whose artist is named in the prompt first', function () {
    $ranker = new ReleaseRanker;

    $match = DiscogsRelease::factory()->create(['artist' => 'Radiohead', 'title' => 'In Rainbows']);
    DiscogsCollectionItem::factory()->for($match, 'release')->create();
    $other = DiscogsRelease::factory()->create(['artist' => 'Someone Else', 'title' => 'Unrelated Album']);
    DiscogsCollectionItem::factory()->for($other, 'release')->create();

    $candidates = DiscogsRelease::query()->with(['genres', 'styles'])->get();

    $ranked = $ranker->rank('play some radiohead', $candidates, 5);

    expect($ranked->first()->discogs_id)->toBe($match->discogs_id);
});

it('ranks a release with matching genres/styles above one with none', function () {
    $ranker = new ReleaseRanker;

    $match = DiscogsRelease::factory()->withGenres(['Jazz'])->withStyles(['Ambient'])->create();
    $other = DiscogsRelease::factory()->withGenres(['Metal'])->withStyles(['Thrash Metal'])->create();

    $candidates = DiscogsRelease::query()->with(['genres', 'styles'])->whereIn('id', [$match->id, $other->id])->get();

    $ranked = $ranker->rank('chill jazz ambient vibes', $candidates, 5);

    expect($ranked->first()->id)->toBe($match->id);
});

it('never returns more items than requested', function () {
    $ranker = new ReleaseRanker;

    DiscogsRelease::factory()->count(10)->create();
    $candidates = DiscogsRelease::query()->with(['genres', 'styles'])->get();

    $ranked = $ranker->rank('anything', $candidates, 3);

    expect($ranked)->toHaveCount(3);
});

it('does not crash on an empty candidate collection', function () {
    $ranker = new ReleaseRanker;

    $ranked = $ranker->rank('anything', collect(), 5);

    expect($ranked)->toBeEmpty();
});

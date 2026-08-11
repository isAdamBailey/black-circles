<?php

use App\Services\CollectionQueryService;
use Illuminate\Http\Request;

/**
 * Paging through a sort whose values tie (several records by the same artist,
 * added on the same day, …) has no defined row order unless a unique column
 * breaks the tie, so the database is free to hand back a release on two pages
 * and another on none. The collection grid pages as the user scrolls, which
 * walks the whole collection, so any instability surfaces as duplicate or
 * missing cards.
 */
it('breaks every sort tie with a unique column so pagination stays stable', function (string $sort) {
    $query = (new CollectionQueryService)->build(
        Request::create('/collection', 'GET', ['sort' => $sort, 'direction' => 'asc'])
    );

    $sql = $query->toSql();
    $orderBy = substr($sql, (int) strripos($sql, 'order by'));

    // Identifier quoting differs between the raw and builder-generated clauses.
    expect(str_replace('"', '', $orderBy))->toContain('discogs_releases.id');
})->with(['value', 'year', 'artist', 'title', 'date_added']);

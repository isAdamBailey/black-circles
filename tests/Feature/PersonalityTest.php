<?php

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use Illuminate\Support\Facades\Http;

it('renders the personality page', function () {
    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Personality/Show'));
});

it('returns AI-generated personality insight when token is set', function () {
    config(['services.huggingface.token' => 'test-token']);

    Http::fake([
        'router.huggingface.co/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'You are a creative and introspective person who values depth.']],
            ],
        ], 200),
    ]);

    $release = DiscogsRelease::factory()
        ->withGenres(['Rock'])
        ->withStyles(['Post-Punk'])
        ->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Personality/Show')
            ->where('insight', 'You are a creative and introspective person who values depth.')
        );
});

it('returns empty insight when no huggingface token is configured', function () {
    config(['services.huggingface.token' => '']);

    $release = DiscogsRelease::factory()->create();
    DiscogsCollectionItem::factory()->for($release, 'release')->create();

    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Personality/Show')
            ->where('insight', '')
        );
});

it('returns empty insight when collection is empty', function () {
    config(['services.huggingface.token' => 'test-token']);

    $this->get(route('personality.show'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Personality/Show')
            ->where('insight', '')
        );
});

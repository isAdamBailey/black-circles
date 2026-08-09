<?php

use App\Models\Setting;

it('returns home data as json', function () {
    $this->getJson(route('api.home'))
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['moods', 'username', 'insight'],
        ]);
});

it('includes personality insight in the api home response', function () {
    Setting::set('personality_insight', 'This collection reveals a listener who values depth and atmosphere.');

    $this->getJson(route('api.home'))
        ->assertStatus(200)
        ->assertJsonPath('data.insight', 'This collection reveals a listener who values depth and atmosphere.');
});

it('returns empty insight in api home when no insight stored in settings', function () {
    $this->getJson(route('api.home'))
        ->assertStatus(200)
        ->assertJsonPath('data.insight', '');
});

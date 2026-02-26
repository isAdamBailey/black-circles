<?php

it('renders the home page at root', function () {
    $this->get('/')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Home')->has('moods'));
});

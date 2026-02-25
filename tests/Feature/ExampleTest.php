<?php

it('redirects the root URL to the collection', function () {
    $this->get('/')->assertRedirect(route('collection.index'));
});

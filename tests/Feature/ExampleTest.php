<?php

it('redirects the index to the dashboard', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('dashboard'));
});

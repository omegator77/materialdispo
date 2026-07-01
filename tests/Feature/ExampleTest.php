<?php

it('redirects the root url to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

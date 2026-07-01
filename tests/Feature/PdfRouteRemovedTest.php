<?php

test('the old unauthenticated placeholder pdf route no longer exists', function () {
    $this->get('/pdf')->assertNotFound();
});

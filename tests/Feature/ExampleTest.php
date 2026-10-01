<?php

test('halaman root mengarahkan ke panel admin', function () {
    $response = $this->get('/');

    $response->assertRedirect('/admin');
});

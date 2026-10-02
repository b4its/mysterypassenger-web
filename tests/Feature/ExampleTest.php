<?php

test('halaman root menampilkan halaman selamat datang', function () {
    $response = $this->get('/');

    $response->assertOk();
});

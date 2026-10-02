<?php

use Illuminate\Support\Facades\Storage;

it('mengembalikan 404 untuk path unduhan yang tidak valid, bukan 500', function () {
    actingAsAdmin();

    $this->get(route('exports.download', ['path' => 'bukan-payload-terenkripsi']))
        ->assertNotFound();
});

it('menolak path terenkripsi yang menunjuk ke luar direktori exports', function () {
    actingAsAdmin();

    $this->get(route('exports.download', ['path' => encrypt('rahasia.txt')]))
        ->assertNotFound();
});

it('mengunduh berkas ekspor yang valid', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/paket.zip', 'isi-zip');

    actingAsAdmin();

    $this->get(route('exports.download', ['path' => encrypt('exports/paket.zip')]))
        ->assertOk();
});

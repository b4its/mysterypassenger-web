<?php

use App\Models\User;

it('menampilkan halaman selamat datang di route root', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Selamat Datang')
        ->assertSee('Mystery Passenger');
});

it('menampilkan tiga pilihan utama pada halaman selamat datang', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Buat Laporan')
        ->assertSee('Lihat Laporan')
        ->assertSee('Buat / Kustom Pertanyaan');
});

it('menautkan setiap pilihan ke halaman terkait di panel', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee(route('filament.admin.resources.surveys.create'), false)
        ->assertSee(route('filament.admin.resources.surveys.index'), false)
        ->assertSee(route('filament.admin.resources.form-templates.index'), false);
});

it('menampilkan ajakan masuk bagi tamu', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Anda belum masuk')
        ->assertSee(route('filament.admin.auth.login'), false);
});

it('menampilkan identitas pengguna dan tautan dasbor setelah masuk', function () {
    $user = User::factory()->admin()->create(['name' => 'Petugas Uji']);

    $response = $this->actingAs($user)->get('/');

    $response->assertOk()
        ->assertSee('Petugas Uji')
        ->assertSee('Dasbor')
        ->assertDontSee('Anda belum masuk');
});

it('tetap dapat diakses tanpa autentikasi', function () {
    $this->get('/')->assertOk();
});

it('memakai controller LandingController untuk route root', function () {
    expect(route('landing'))->toBe(url('/'));

    $this->get('/')
        ->assertViewIs('landing')
        ->assertViewHas('actions', fn (array $actions) => count($actions) === 3);
});

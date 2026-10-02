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

it('menautkan aksi laporan ke aplikasi web non-filament dan kustom pertanyaan ke panel admin', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee(route('app.surveys.create'), false)
        ->assertSee(route('app.surveys.index'), false)
        ->assertSee(route('filament.admin.resources.form-templates.index'), false);
});

it('menampilkan ajakan masuk bagi tamu dengan tautan ke halaman login web', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Anda belum masuk')
        ->assertSee(route('login'), false);
});

it('menampilkan identitas pengguna dan tautan dasbor yang sesuai setelah masuk', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin Uji']);
    $response = $this->actingAs($admin)->get('/');

    $response->assertOk()
        ->assertSee('Admin Uji')
        ->assertSee('Buka Aplikasi')
        ->assertSee(url('/admin'), false)
        ->assertDontSee('Anda belum masuk');

    $surveyor = User::factory()->surveyor()->create(['name' => 'Surveyor Uji']);
    $responseSurveyor = $this->actingAs($surveyor)->get('/');

    $responseSurveyor->assertOk()
        ->assertSee('Surveyor Uji')
        ->assertSee('Buka Aplikasi')
        ->assertSee(route('app.dashboard'), false);
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

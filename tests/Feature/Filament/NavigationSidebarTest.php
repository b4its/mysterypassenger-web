<?php

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;

it('menampilkan seluruh grup dan resource navigasi pada sidebar untuk peran Admin', function () {
    actingAsAdmin();

    $navigation = Filament::getNavigation();

    $allVisibleLabels = collect($navigation)
        ->flatMap(fn ($group) => $group->getItems())
        ->filter(fn ($item) => $item->isVisible())
        ->map(fn ($item) => $item->getLabel())
        ->all();

    expect($allVisibleLabels)->toContain(
        'Beranda',
        'Survei',
        'Pengaturan Pertanyaan',
        'Jenis Transportasi',
        'Template Formulir',
        'Pengaturan Cetak',
        'Pengguna',
    );
});

it('hanya menampilkan menu Survei dan Beranda pada sidebar untuk peran Surveyor', function () {
    actingAsSurveyor();

    $navigation = Filament::getNavigation();

    $allVisibleLabels = collect($navigation)
        ->flatMap(fn ($group) => $group->getItems())
        ->filter(fn ($item) => $item->isVisible())
        ->map(fn ($item) => $item->getLabel())
        ->all();

    expect($allVisibleLabels)->toContain('Beranda', 'Survei')
        ->and($allVisibleLabels)->not->toContain(
            'Pengaturan Pertanyaan',
            'Jenis Transportasi',
            'Template Formulir',
            'Pengaturan Cetak',
            'Pengguna',
        );
});

it('menampilkan menu Survei dan Jenis Transportasi untuk peran Reviewer', function () {
    actingAsReviewer();

    $navigation = Filament::getNavigation();

    $allVisibleLabels = collect($navigation)
        ->flatMap(fn ($group) => $group->getItems())
        ->filter(fn ($item) => $item->isVisible())
        ->map(fn ($item) => $item->getLabel())
        ->all();

    expect($allVisibleLabels)->toContain('Beranda', 'Survei', 'Jenis Transportasi')
        ->and($allVisibleLabels)->not->toContain(
            'Pengaturan Pertanyaan',
            'Template Formulir',
            'Pengaturan Cetak',
            'Pengguna',
        );
});

it('membuat pengguna filament dengan peran Admin secara eksplisit via CLI', function () {
    $this->artisan('make:filament-user', [
        '--name' => 'Admin Baru',
        '--email' => 'adminbaru@example.com',
        '--password' => 'secret1234',
        '--role' => 'admin',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'adminbaru@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Admin);
});

it('dapat mengubah peran pengguna melalui perintah user:role dan user:promote-admin', function () {
    $user = User::factory()->create([
        'email' => 'petugas@example.com',
        'role' => UserRole::Surveyor,
    ]);

    $this->artisan('user:promote-admin', [
        'user' => 'petugas@example.com',
    ])->assertSuccessful();

    expect($user->fresh()->role)->toBe(UserRole::Admin);

    $this->artisan('user:role', [
        'user' => 'petugas@example.com',
        'role' => 'reviewer',
    ])->assertSuccessful();

    expect($user->fresh()->role)->toBe(UserRole::Reviewer);
});

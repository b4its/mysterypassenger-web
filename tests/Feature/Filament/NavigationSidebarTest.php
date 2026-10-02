<?php

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;

it('mengizinkan peran Admin mengakses panel Filament dan menampilkan seluruh grup navigasi', function () {
    $admin = actingAsAdmin();

    expect($admin->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();

    $this->get('/admin')->assertOk();

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

it('melarang peran Surveyor mengakses panel admin atau filament', function () {
    $surveyor = actingAsSurveyor();

    expect($surveyor->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();

    $this->get('/admin')->assertForbidden();
    $this->get('/admin/question-setup')->assertForbidden();
    $this->get('/admin/form-templates')->assertForbidden();
});

it('melarang peran Reviewer mengakses panel admin atau filament', function () {
    $reviewer = actingAsReviewer();

    expect($reviewer->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();

    $this->get('/admin')->assertForbidden();
    $this->get('/admin/question-setup')->assertForbidden();
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

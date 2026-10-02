<?php

use App\Enums\SurveyStatus;
use App\Filament\Pages\Beranda;
use App\Filament\Pages\QuestionSetup;
use App\Models\Survey;

use function Pest\Livewire\livewire;

it('merender halaman beranda untuk admin', function () {
    actingAsAdmin();

    livewire(Beranda::class)
        ->assertOk()
        ->assertSee('Halo,');
});

it('menampilkan kartu aksi utama di beranda', function () {
    actingAsAdmin();

    livewire(Beranda::class)
        ->assertOk()
        ->assertSee('Mulai Pelaporan')
        ->assertSee('Riwayat Pelaporan')
        ->assertSee('Pengaturan Pertanyaan');
});

it('menyusun ringkasan aktivitas survei milik pengguna', function () {
    $surveyor = actingAsSurveyor();

    Survey::factory()->count(2)->create(['user_id' => $surveyor->id, 'status' => SurveyStatus::Draft]);
    Survey::factory()->create(['user_id' => $surveyor->id, 'status' => SurveyStatus::Approved]);
    Survey::factory()->create(); // milik orang lain (tidak dihitung)

    $summary = livewire(Beranda::class)->instance()->getSummary();

    expect($summary['total'])->toBe(3)
        ->and($summary['draft'])->toBe(2)
        ->and($summary['approved'])->toBe(1);
});

it('hanya menampilkan kartu pengaturan formulir untuk admin', function () {
    actingAsSurveyor();

    $keys = collect(livewire(Beranda::class)->instance()->getCards())->pluck('key')->all();

    expect($keys)->toContain('start', 'history')
        ->and($keys)->not->toContain('settings', 'modes');
});

it('menampilkan kartu pengelolaan formulir untuk admin', function () {
    actingAsAdmin();

    $keys = collect(livewire(Beranda::class)->instance()->getCards())->pluck('key')->all();

    expect($keys)->toContain('start', 'history', 'settings', 'modes');
});

it('menjadikan Beranda sebagai halaman utama panel', function () {
    expect(Beranda::getRoutePath(Filament\Facades\Filament::getPanel('admin')))->toBe('/');
});

it('merender halaman pengaturan pertanyaan untuk admin', function () {
    actingAsAdmin();

    livewire(QuestionSetup::class)
        ->assertOk()
        ->assertSee('Pertanyaan Utama')
        ->assertSee('Buat Baru')
        ->assertSee('Jenis Kendaraan');
});

it('menolak akses pengaturan pertanyaan bagi non-admin', function () {
    actingAsSurveyor();

    expect(QuestionSetup::canAccess())->toBeFalse();
});

it('mengizinkan akses pengaturan pertanyaan bagi admin', function () {
    actingAsAdmin();

    expect(QuestionSetup::canAccess())->toBeTrue();
});

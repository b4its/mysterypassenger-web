<?php

use App\Enums\OutputSection;
use App\Filament\Resources\ReportSettings\Pages\EditReportSetting;
use App\Models\ReportSetting;
use App\Models\Survey;
use App\Models\TransportMode;

use function Pest\Livewire\livewire;

beforeEach(fn () => actingAsAdmin());

it('menyediakan aksi pratinjau PDF pada pengaturan cetak', function () {
    $mode = TransportMode::factory()->create();
    $setting = ReportSetting::factory()->create(['transport_mode_id' => $mode->id]);

    livewire(EditReportSetting::class, ['record' => $setting->getKey()])
        ->assertOk()
        ->assertActionExists('preview')
        ->assertActionVisible('preview');
});

it('merender iframe PDF pada partial pratinjau', function () {
    $survey = Survey::factory()->submitted()->create();

    $html = view('filament.partials.pdf-preview', [
        'url' => route('surveys.pdf', ['survey' => $survey, 'section' => OutputSection::Checklist->value]),
        'filename' => 'ceklist.pdf',
    ])->render();

    expect($html)
        ->toContain('<iframe')
        ->toContain(route('surveys.pdf', ['survey' => $survey, 'section' => OutputSection::Checklist->value]))
        ->toContain('ceklist.pdf');
});

it('merender pesan kosong bila tidak ada survei', function () {
    expect(view('filament.partials.pdf-preview-empty')->render())
        ->toContain('Belum ada survei');
});

it('menampilkan modal pratinjau ketika aksi dipanggil (tanpa redirect)', function () {
    $mode = TransportMode::factory()->create();
    $setting = ReportSetting::factory()->create(['transport_mode_id' => $mode->id]);
    Survey::factory()->for($mode)->submitted()->create(['executed_at' => now()]);

    livewire(EditReportSetting::class, ['record' => $setting->getKey()])
        ->mountAction('preview')
        ->assertNoRedirect()
        ->assertSuccessful();
});

<?php

use App\Filament\Resources\ReportSettings\Pages\EditReportSetting;
use App\Models\ReportSetting;
use App\Models\Survey;
use App\Models\TransportMode;
use Filament\Notifications\Notification;

use function Pest\Livewire\livewire;

beforeEach(fn () => actingAsAdmin());

it('membuka pratinjau cetak bila ada survei pada moda', function () {
    $mode = TransportMode::factory()->create();
    $setting = ReportSetting::factory()->create(['transport_mode_id' => $mode->id]);
    $survey = Survey::factory()->for($mode)->submitted()->create(['executed_at' => now()]);

    livewire(EditReportSetting::class, ['record' => $setting->getKey()])
        ->callAction('preview')
        ->assertRedirect(route('surveys.print', [
            'survey' => $survey,
            'section' => 'checklist',
            'auto' => 0,
        ]));
});

it('memberi notifikasi bila belum ada survei untuk pratinjau', function () {
    $mode = TransportMode::factory()->create();
    $setting = ReportSetting::factory()->create(['transport_mode_id' => $mode->id]);

    livewire(EditReportSetting::class, ['record' => $setting->getKey()])
        ->callAction('preview')
        ->assertNoRedirect();

    Notification::assertNotified('Belum ada survei untuk pratinjau');
});

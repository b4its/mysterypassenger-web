<?php

use App\Enums\SurveyStatus;
use App\Filament\Resources\Surveys\Pages\ListSurveys;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Filament\Resources\TransportModes\TransportModeResource;
use App\Models\Survey;
use App\Models\TransportMode;

use function Pest\Livewire\livewire;

it('surveyor hanya melihat surveinya sendiri', function () {
    $surveyor = actingAsSurveyor();

    $mine = Survey::factory()->count(2)->create(['user_id' => $surveyor->id]);
    $others = Survey::factory()->count(3)->create();

    livewire(ListSurveys::class)
        ->assertCanSeeTableRecords($mine)
        ->assertCanNotSeeTableRecords($others);
});

it('reviewer melihat semua survei', function () {
    actingAsReviewer();

    $surveys = Survey::factory()->count(3)->create();

    livewire(ListSurveys::class)
        ->assertCanSeeTableRecords($surveys);
});

it('memfilter berdasarkan moda transportasi', function () {
    actingAsAdmin();

    $ship = TransportMode::factory()->create();
    $bus = TransportMode::factory()->create();

    $shipSurveys = Survey::factory()->count(2)->create(['transport_mode_id' => $ship->id]);
    $busSurveys = Survey::factory()->count(2)->create(['transport_mode_id' => $bus->id]);

    livewire(ListSurveys::class)
        ->filterTable('transport_mode_id', [$ship->id])
        ->assertCanSeeTableRecords($shipSurveys)
        ->assertCanNotSeeTableRecords($busSurveys);
});

it('surveyor tidak dapat mengakses resource moda transportasi', function () {
    actingAsSurveyor();

    expect(SurveyResource::canAccess())->toBeTrue();
    expect(TransportModeResource::canAccess())->toBeFalse();
});

it('menampilkan badge navigasi untuk survei menunggu review', function () {
    Survey::factory()->count(2)->create(['status' => SurveyStatus::Submitted]);
    Survey::factory()->create(['status' => SurveyStatus::Draft]);

    actingAsReviewer();

    expect(SurveyResource::getNavigationBadge())->toBe('2');
});

<?php

use App\Enums\SurveyStatus;
use App\Filament\Exports\SurveyExporter;
use App\Filament\Resources\Surveys\Pages\ListSurveys;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Filament\Resources\Surveys\Tables\SurveysTable;
use App\Filament\Resources\TransportModes\TransportModeResource;
use App\Models\FormTemplate;
use App\Models\Survey;
use App\Models\SurveyFieldValue;
use App\Models\TemplateField;
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

it('merender kolom dinamis dari template_fields tanpa error serialisasi cache', function () {
    actingAsAdmin();

    // Gunakan cache database (bukan array) agar regresi "Serialization of Closure"
    // ikut tertangkap, karena kolom dinamis pernah di-cache sebagai objek TextColumn.
    config()->set('cache.default', 'database');
    cache()->flush();

    $template = FormTemplate::factory()->create();
    TemplateField::factory()->create([
        'form_template_id' => $template->id,
        'key' => 'nama_kapal',
        'label' => 'Nama Kapal',
        'show_in_table' => true,
    ]);

    $survey = Survey::factory()->for($template)->create();
    SurveyFieldValue::factory()->create([
        'survey_id' => $survey->id,
        'field_key' => 'nama_kapal',
        'field_label' => 'Nama Kapal',
        'value' => 'KM Test',
        'value_text' => 'KM Test',
    ]);

    $columns = SurveysTable::dynamicFieldColumns();

    expect($columns)->not->toBeEmpty();

    livewire(ListSurveys::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$survey]);
});

it('tidak menyimpan objek kolom (closure) ke cache', function () {
    actingAsAdmin();
    config()->set('cache.default', 'database');
    cache()->flush();

    TemplateField::factory()->create([
        'key' => 'nama_aset',
        'label' => 'Nama Aset',
        'show_in_table' => true,
    ]);

    // Tidak boleh melempar "Serialization of 'Closure' is not allowed".
    SurveysTable::dynamicFieldColumns();
    SurveyExporter::getColumns();

    expect(cache()->has('survey_table_dynamic_fields'))->toBeTrue()
        ->and(cache()->get('survey_table_dynamic_fields'))->toBeArray();
});

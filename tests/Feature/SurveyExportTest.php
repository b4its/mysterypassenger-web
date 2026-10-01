<?php

use App\Filament\Exports\SurveyExporter;
use App\Models\FormTemplate;
use App\Models\Survey;
use App\Models\TemplateField;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

it('mendaftarkan exporter Survei dengan format CSV dan XLSX', function () {
    expect(SurveyExporter::getColumns())->not->toBeEmpty();

    $export = new Export;
    $exporter = new SurveyExporter($export, [], []);

    expect($exporter->getFormats())->toContain(ExportFormat::Csv)
        ->and($exporter->getFormats())->toContain(ExportFormat::Xlsx)
        ->and($exporter->getFileDisk())->toBe('local');
});

it('menyediakan kolom lengkap tanpa error resolusi', function () {
    $columns = SurveyExporter::getColumns();

    foreach ($columns as $column) {
        expect($column->getName())->not->toBeEmpty();
    }

    expect($columns)->not->toBeEmpty();
});

it('menerapkan proteksi formula injection secara global', function () {
    $column = ExportColumn::make('value_text');

    expect($column->shouldPreventFormulaInjection())->toBeTrue();
});

it('menyertakan kolom dinamis dari template_fields', function () {
    $template = FormTemplate::factory()->create();
    TemplateField::factory()->create([
        'form_template_id' => $template->id,
        'key' => 'nama_kapal',
        'label' => 'Nama Kapal',
    ]);

    cache()->forget('survey_export_dynamic_fields');

    $names = collect(SurveyExporter::getColumns())->map(fn ($c) => $c->getName());

    expect($names)->toContain('field_nama_kapal');
});

it('membatasi query export surveyor hanya pada datanya sendiri', function () {
    $surveyor = actingAsSurveyor();

    $mine = Survey::factory()->count(2)->create(['user_id' => $surveyor->id]);
    Survey::factory()->count(3)->create();

    $query = SurveyExporter::modifyQuery(Survey::query())->visibleTo($surveyor);

    expect($query->count())->toBe(2)
        ->and($query->pluck('id')->all())->toEqualCanonicalizing($mine->pluck('id')->all());
});

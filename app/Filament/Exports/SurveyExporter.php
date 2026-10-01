<?php

namespace App\Filament\Exports;

use App\Models\Survey;
use App\Models\TemplateField;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class SurveyExporter extends Exporter
{
    protected static ?string $model = Survey::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code')->label('No. Dokumen'),
            ExportColumn::make('transportMode.name')->label('Jenis Transportasi'),
            ExportColumn::make('formTemplate.name')->label('Template'),
            ExportColumn::make('template_version')->label('Versi Template'),
            ExportColumn::make('evaluator_name')->label('Evaluator'),
            ExportColumn::make('surveyor.name')->label('Surveyor'),
            ExportColumn::make('executed_at')->label('Waktu Pelaksanaan'),
            ExportColumn::make('location_text')->label('Lokasi'),
            ExportColumn::make('latitude')->label('Latitude')->enabledByDefault(false),
            ExportColumn::make('longitude')->label('Longitude')->enabledByDefault(false),
            ExportColumn::make('status')->label('Status')
                ->formatStateUsing(fn ($state) => $state?->getLabel()),
            ExportColumn::make('total_score')->label('Skor'),
            ExportColumn::make('max_score')->label('Skor Maksimum'),
            ExportColumn::make('score_percentage')->label('Persentase'),
            ExportColumn::make('is_passed')->label('Lulus')
                ->formatStateUsing(fn ($state) => match ($state) {
                    true => 'Ya', false => 'Tidak', default => '-',
                }),
            ExportColumn::make('answers_count')->counts('answers')->label('Jumlah Jawaban'),
            ExportColumn::make('summary_note')->label('Catatan Ringkasan')->enabledByDefault(false),
            ExportColumn::make('review_note')->label('Catatan Review')->enabledByDefault(false),
            ExportColumn::make('submitted_at')->label('Dikirim'),
            ExportColumn::make('reviewed_at')->label('Direview'),

            ...static::dynamicFieldColumns(),
        ];
    }

    /** @return array<ExportColumn> */
    protected static function dynamicFieldColumns(): array
    {
        return cache()->remember('survey_export_dynamic_columns', now()->addMinutes(10), function () {
            return TemplateField::query()
                ->get()
                ->unique('key')
                ->map(fn (TemplateField $field) => ExportColumn::make("field_{$field->key}")
                    ->label($field->label)
                    ->state(fn (Survey $record) => $record->fieldValues
                        ->firstWhere('field_key', $field->key)?->value_text))
                ->values()
                ->all();
        });
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['transportMode', 'formTemplate', 'surveyor', 'fieldValues']);
    }

    public function getFormats(): array
    {
        return [ExportFormat::Xlsx, ExportFormat::Csv];
    }

    public function getFileDisk(): string
    {
        return 'local';           // disk privat, bukan public
    }

    public function getFileName(Export $export): string
    {
        return 'survei-'.now()->format('Ymd-His').'-'.$export->getKey();
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "{$export->successful_rows} survei berhasil diekspor.";
    }
}

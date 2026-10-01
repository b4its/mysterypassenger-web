<?php

namespace App\Filament\Resources\Surveys\Tables;

use App\Enums\SurveyStatus;
use App\Filament\Exports\SurveyExporter;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Jobs\GenerateSurveyPdfBundle;
use App\Models\Survey;
use App\Models\TemplateField;
use App\Services\SurveyStateMachine;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SurveysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('executed_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('No. Dokumen')->searchable()->sortable()
                    ->copyable()->weight('bold'),

                TextColumn::make('transportMode.name')->label('Moda')->badge()
                    ->color(fn (Survey $record) => $record->transportMode?->color)->sortable(),

                TextColumn::make('formTemplate.name')->label('Template')
                    ->description(fn (Survey $record) => "v{$record->template_version}")
                    ->toggleable(isToggledHiddenByDefault: true),

                ...self::dynamicFieldColumns(),

                TextColumn::make('evaluator_name')->label('Evaluator')->searchable(),
                TextColumn::make('surveyor.name')->label('Surveyor')->searchable()->toggleable(),
                TextColumn::make('executed_at')->label('Pelaksanaan')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('status')->badge()->sortable(),

                TextColumn::make('score_percentage')->label('Skor')->sortable()
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((float) $state, 1).'%')
                    ->badge()
                    ->color(fn (Survey $record) => match (true) {
                        $record->score_percentage === null => 'gray',
                        $record->is_passed === true => 'success',
                        default => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('transport_mode_id')->relationship('transportMode', 'name')->label('Moda')->multiple()->preload(),
                SelectFilter::make('form_template_id')->relationship('formTemplate', 'name')->label('Template')->searchable()->preload(),
                SelectFilter::make('status')->options(SurveyStatus::class)->multiple(),
                SelectFilter::make('user_id')->relationship('surveyor', 'name')->label('Surveyor')->searchable()
                    ->visible(fn () => ! auth()->user()?->isSurveyor()),
                Filter::make('executed_at')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('executed_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('executed_at', '<=', $d))),
                TernaryFilter::make('is_passed')->label('Lulus'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('fill')
                    ->label('Isi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn (Survey $r) => auth()->user()?->can('update', $r) ?? false)
                    ->url(fn (Survey $r) => SurveyResource::getUrl('fill', ['record' => $r])),

                ViewAction::make()->label('Detail'),

                ActionGroup::make([
                    Action::make('printChecklist')
                        ->label('Print Ceklist')
                        ->icon('heroicon-o-printer')
                        ->url(fn (Survey $r) => route('surveys.print', ['survey' => $r, 'section' => 'checklist']), shouldOpenInNewTab: true),

                    Action::make('printReport')
                        ->label('Print Laporan')
                        ->icon('heroicon-o-printer')
                        ->url(fn (Survey $r) => route('surveys.print', ['survey' => $r, 'section' => 'report']), shouldOpenInNewTab: true),

                    Action::make('pdfChecklist')
                        ->label('PDF Ceklist')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (Survey $r) => route('surveys.pdf', ['survey' => $r, 'section' => 'checklist'])),

                    Action::make('pdfReport')
                        ->label('PDF Laporan')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (Survey $r) => route('surveys.pdf', ['survey' => $r, 'section' => 'report'])),
                ])
                    ->label('Cetak & Export')
                    ->icon('heroicon-o-printer')
                    ->button()
                    ->color('gray'),

                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->visible(fn (Survey $r) => (auth()->user()?->can('review', $r) ?? false) && $r->status->allowedTransitions() !== [])
                    ->schema([
                        Select::make('status')
                            ->label('Ubah Status')
                            ->options(fn (Survey $r) => collect($r->status->allowedTransitions())
                                ->mapWithKeys(fn (SurveyStatus $s) => [$s->value => $s->getLabel()])
                                ->all())
                            ->required()
                            ->native(false)
                            ->live(),
                        Textarea::make('review_note')
                            ->label('Catatan Review')
                            ->rows(3)
                            ->required(fn (callable $get) => $get('status') === SurveyStatus::Rejected->value),
                    ])
                    ->action(fn (Survey $r, array $data, SurveyStateMachine $machine) => $machine->transition(
                        $r,
                        SurveyStatus::from($data['status']),
                        $data['review_note'] ?? null,
                    )),

                DeleteAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Export CSV/XLSX')
                    ->exporter(SurveyExporter::class)
                    ->modifyQueryUsing(fn (Builder $q) => $q->visibleTo(auth()->user())),
            ])
            ->toolbarActions([
                BulkAction::make('pdfBundle')
                    ->label('Unduh PDF (ZIP)')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->action(fn (Collection $records) => GenerateSurveyPdfBundle::dispatch(
                        $records->pluck('id')->all(),
                        auth()->id(),
                    ))
                    ->deselectRecordsAfterCompletion()
                    ->successNotificationTitle('Paket PDF sedang diproses. Notifikasi akan dikirim setelah selesai.'),

                ExportBulkAction::make()->exporter(SurveyExporter::class),

                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    /**
     * Kolom tabel untuk setiap template_field dengan show_in_table = true.
     *
     * Metadata field (key+label) di-cache, BUKAN objek TextColumn — objek kolom
     * memuat closure yang tidak dapat diserialisasi oleh cache driver database.
     *
     * @return array<int, TextColumn>
     */
    public static function dynamicFieldColumns(): array
    {
        $fields = cache()->remember('survey_table_dynamic_fields', now()->addMinutes(10), function () {
            return TemplateField::query()
                ->where('show_in_table', true)
                ->get(['key', 'label'])
                ->unique('key')
                ->map(fn (TemplateField $field) => ['key' => $field->key, 'label' => $field->label])
                ->values()
                ->all();
        });

        return collect($fields)
            ->map(fn (array $field) => TextColumn::make("field_{$field['key']}")
                ->label($field['label'])
                ->getStateUsing(fn (Survey $record) => $record->field($field['key']))
                ->toggleable()
                ->searchable(query: fn (Builder $q, string $search) => $q->whereHas(
                    'fieldValues',
                    fn (Builder $fv) => $fv->where('field_key', $field['key'])
                        ->where('value_text', 'like', "%{$search}%"),
                )))
            ->all();
    }
}

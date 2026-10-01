<?php

namespace App\Filament\Resources\FormTemplates\Pages;

use App\Enums\AnswerType;
use App\Enums\EvidenceRequirement;
use App\Enums\OutputSection;
use App\Filament\Resources\FormTemplates\FormTemplateResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;

class BuildFormTemplate extends Page implements HasSchemas
{
    use InteractsWithRecord;
    use InteractsWithSchemas;

    protected static string $resource = FormTemplateResource::class;

    protected string $view = 'filament.pages.build-form-template';

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();

        $this->form->fill();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canAccess(), 403);
    }

    public function getTitle(): string
    {
        return "Susun Pertanyaan — {$this->record->name} v{$this->record->version}";
    }

    public function getSubheading(): ?string
    {
        return $this->record->transportMode?->name.' · status: '.$this->record->status->getLabel();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->record($this->record)
            ->statePath('data')
            ->disabled(fn () => ! $this->record->isEditable())
            ->components([
                Section::make('Indikator & Pertanyaan')
                    ->description('Indikator = kelompok penilaian (mis. Tangibles). Pertanyaan = sub-indikator di dalamnya.')
                    ->schema([
                        Repeater::make('questionGroups')
                            ->relationship('rootGroups')
                            ->label('Indikator')
                            ->hiddenLabel()
                            ->orderColumn('sort_order')
                            ->collapsible()
                            ->collapsed()
                            ->cloneable()
                            ->addActionLabel('Tambah Indikator')
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->schema([
                                Grid::make(4)->schema([
                                    TextInput::make('name')
                                        ->label('Nama Indikator')
                                        ->placeholder('Tangibles, Reliability, …')
                                        ->required()
                                        ->columnSpan(2),

                                    Select::make('output_section')
                                        ->label('Masuk Dokumen')
                                        ->options(OutputSection::class)
                                        ->default(OutputSection::Checklist)
                                        ->required()
                                        ->native(false)
                                        ->helperText('Menentukan indikator ini dicetak di Ceklist, Laporan, atau keduanya.'),

                                    TextInput::make('weight')
                                        ->label('Bobot')
                                        ->numeric()
                                        ->default(1)
                                        ->step(0.01)
                                        ->minValue(0),
                                ]),

                                Textarea::make('description')->label('Keterangan')->rows(2),

                                Repeater::make('questions')
                                    ->relationship('questions')
                                    ->label('Pertanyaan')
                                    ->orderColumn('sort_order')
                                    ->collapsible()
                                    ->cloneable()
                                    ->addActionLabel('Tambah Pertanyaan')
                                    ->itemLabel(fn (array $state): ?string => str($state['text'] ?? '')->limit(70)->toString())
                                    ->mutateRelationshipDataBeforeCreateUsing(
                                        fn (array $data): array => $data + ['form_template_id' => $this->record->id],
                                    )
                                    ->schema([
                                        Textarea::make('text')
                                            ->label('Teks Pertanyaan')
                                            ->required()
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        Grid::make(4)->schema([
                                            TextInput::make('code')->label('Kode')->placeholder('TAN-01')->maxLength(32),

                                            Select::make('answer_type')
                                                ->label('Tipe Jawaban')
                                                ->options(AnswerType::class)
                                                ->default(AnswerType::Boolean)
                                                ->required()
                                                ->live()
                                                ->native(false),

                                            Select::make('evidence_requirement')
                                                ->label('Bukti Foto')
                                                ->options(EvidenceRequirement::class)
                                                ->default(EvidenceRequirement::Optional)
                                                ->native(false),

                                            TextInput::make('evidence_max')
                                                ->label('Maks. Bukti')
                                                ->numeric()->default(3)->minValue(0)->maxValue(20),
                                        ]),

                                        // Konfigurasi skala, hanya untuk tipe rating
                                        Grid::make(3)
                                            ->visible(fn (callable $get) => $get('answer_type') === AnswerType::Rating->value)
                                            ->schema([
                                                TextInput::make('options.scale.min')->label('Skala Min')->numeric()->default(1),
                                                TextInput::make('options.scale.max')->label('Skala Maks')->numeric()->default(5),
                                                TextInput::make('options.scale.suffix')->label('Satuan')->placeholder('poin'),
                                            ]),

                                        // Opsi pilihan, untuk select_single / select_multiple
                                        Repeater::make('questionOptions')
                                            ->relationship('questionOptions')
                                            ->label('Opsi Jawaban')
                                            ->visible(fn (callable $get) => in_array($get('answer_type'), [
                                                AnswerType::SelectSingle->value,
                                                AnswerType::SelectMultiple->value,
                                            ], true))
                                            ->orderColumn('sort_order')
                                            ->addActionLabel('Tambah Opsi')
                                            ->defaultItems(2)
                                            ->columnSpanFull()
                                            ->table([
                                                Repeater\TableColumn::make('Nilai'),
                                                Repeater\TableColumn::make('Label'),
                                                Repeater\TableColumn::make('Skor'),
                                                Repeater\TableColumn::make('Patuh?'),
                                            ])
                                            ->schema([
                                                TextInput::make('value')->required()->maxLength(64),
                                                TextInput::make('label')->required(),
                                                TextInput::make('score')->numeric()->default(0)->step(0.01),
                                                Toggle::make('is_compliant')->inline(false),
                                            ]),

                                        Grid::make(4)->schema([
                                            Toggle::make('is_required')->label('Wajib Dijawab')->default(true),
                                            Toggle::make('allow_note')->label('Izinkan Catatan')->default(true),
                                            TextInput::make('weight')->label('Bobot')->numeric()->default(1)->step(0.01),
                                            TextInput::make('max_score')->label('Skor Maks')->numeric()->default(1)->step(0.01),
                                        ]),

                                        Textarea::make('helper_text')->label('Petunjuk Pengisian')->rows(1)->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Struktur')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->record->isEditable())
                ->action(function () {
                    $state = $this->form->getState();

                    $this->form->getRecord()->save();
                    $this->form->saveRelationships();

                    Notification::make()->title('Struktur template disimpan')->success()->send();
                }),

            Action::make('preview')
                ->label('Pratinjau Formulir')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => route('templates.preview', ['template' => $this->record]), shouldOpenInNewTab: true),

            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(FormTemplateResource::getUrl('index')),
        ];
    }
}

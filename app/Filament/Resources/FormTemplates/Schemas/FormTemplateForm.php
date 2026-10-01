<?php

namespace App\Filament\Resources\FormTemplates\Schemas;

use App\Enums\ScoringStrategy;
use App\Enums\TemplateStatus;
use App\Filament\Resources\TransportModes\Schemas\TransportModeForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FormTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template')
                ->columns(2)
                ->schema([
                    Select::make('transport_mode_id')
                        ->label('Jenis Transportasi')
                        ->relationship('transportMode', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->createOptionForm(fn (Schema $s) => TransportModeForm::configure($s)->getComponents()),

                    TextInput::make('name')->label('Nama Template')->required()->maxLength(160),

                    TextInput::make('slug')->required()->maxLength(180)
                        ->helperText('Unik per moda + versi.'),

                    TextInput::make('version')->label('Versi')->numeric()->default(1)
                        ->disabled()->dehydrated()
                        ->helperText('Versi baru dibuat lewat aksi "Versi Baru".'),

                    Select::make('status')
                        ->options(TemplateStatus::class)
                        ->default(TemplateStatus::Draft)
                        ->required()
                        ->native(false),

                    TextInput::make('max_evidence_per_answer')
                        ->label('Maks. Bukti per Jawaban')->numeric()->default(5)->minValue(0)->maxValue(20),

                    Textarea::make('description')->rows(2)->columnSpanFull(),
                ]),

            Section::make('Penilaian')
                ->columns(3)
                ->schema([
                    Toggle::make('scoring_enabled')->label('Aktifkan Skor')->default(true)->live(),

                    Select::make('scoring_strategy')
                        ->label('Metode')
                        ->options(ScoringStrategy::class)
                        ->default(ScoringStrategy::Weighted)
                        ->native(false)
                        ->visible(fn (callable $get) => $get('scoring_enabled')),

                    TextInput::make('passing_score')
                        ->label('Batas Lulus (%)')
                        ->numeric()->minValue(0)->maxValue(100)->suffix('%')
                        ->visible(fn (callable $get) => $get('scoring_enabled')),
                ]),
        ]);
    }
}

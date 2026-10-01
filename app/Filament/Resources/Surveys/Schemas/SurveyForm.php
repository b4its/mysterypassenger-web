<?php

namespace App\Filament\Resources\Surveys\Schemas;

use App\Enums\SurveyStatus;
use App\Models\FormTemplate;
use App\Models\TransportMode;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;

class SurveyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Wizard\Step::make('Pilih Moda & Template')->schema([
                    Select::make('transport_mode_id')
                        ->label('Jenis Transportasi')
                        ->options(fn () => TransportMode::where('is_active', true)
                            ->orderBy('sort_order')->pluck('name', 'id'))
                        ->required()->live()->native(false),

                    Select::make('form_template_id')
                        ->label('Template Formulir')
                        ->options(fn (callable $get) => FormTemplate::query()
                            ->published()
                            ->where('transport_mode_id', $get('transport_mode_id'))
                            ->when(
                                auth()->user()?->isSurveyor(),
                                fn ($q) => $q->whereHas('assignments', fn ($a) => $a->where('user_id', auth()->id())),
                            )
                            ->orderByDesc('version')
                            ->get()
                            ->mapWithKeys(fn ($t) => [$t->id => "{$t->name} (v{$t->version})"]))
                        ->required()->live()->native(false)
                        ->helperText('Hanya template yang sudah diterbitkan dan ditugaskan kepada Anda.'),
                ]),

                Wizard\Step::make('Identitas Awal')->schema([
                    TextInput::make('evaluator_name')
                        ->label('Nama Evaluator')
                        ->required()
                        ->default(fn () => auth()->user()?->name),

                    DateTimePicker::make('executed_at')
                        ->label('Waktu Pelaksanaan')
                        ->required()
                        ->default(now())
                        ->seconds(false)
                        ->displayFormat('d/m/Y H:i'),
                ]),
            ])
                ->columnSpanFull(),
        ]);
    }

    public static function defaultStatus(): SurveyStatus
    {
        return SurveyStatus::Draft;
    }
}

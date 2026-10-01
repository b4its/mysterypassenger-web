<?php

namespace App\Filament\Resources\Surveys\Schemas;

use App\Enums\SurveyStatus;
use App\Filament\Resources\TransportModes\Schemas\TransportModeForm;
use App\Models\FormTemplate;
use App\Models\TransportMode;
use App\Services\TemplateQuestionPreview;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SurveyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Wizard\Step::make('Pilih Moda & Template')->schema([
                    Select::make('transport_mode_id')
                        ->label('Jenis Transportasi')
                        ->relationship(
                            name: 'transportMode',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) => $query->where('is_active', true)->orderBy('sort_order'),
                        )
                        ->createOptionForm(fn (Schema $s) => TransportModeForm::configure($s)->getComponents())
                        ->createOptionUsing(function (array $data) {
                            $mode = TransportMode::create($data);

                            return $mode->getKey();
                        })
                        ->createOptionModalHeading('Tambah Jenis Transportasi')
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
                        ->helperText('Hanya template yang sudah diterbitkan dan ditugaskan kepada Anda.')
                        ->hintAction(self::questionPreviewAction()),
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

    /**
     * Ikon (?) pada select template → modal daftar pertanyaan yang akan ditanyakan.
     */
    public static function questionPreviewAction(): Action
    {
        return Action::make('previewQuestions')
            ->label('Lihat pertanyaan')
            ->icon('heroicon-o-question-mark-circle')
            ->iconButton()
            ->color('gray')
            ->modalHeading('Pertanyaan pada Template Formulir')
            ->modalDescription('Daftar field profil, indikator, dan pertanyaan yang akan ditanyakan.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalWidth('2xl')
            ->modalContent(function (callable $get) {
                $templateId = $get('form_template_id');

                if (! $templateId) {
                    return view('filament.modals.template-questions-empty');
                }

                $template = FormTemplate::find($templateId);

                if (! $template) {
                    return view('filament.modals.template-questions-empty');
                }

                return view('filament.modals.template-questions', [
                    'preview' => app(TemplateQuestionPreview::class)->build($template),
                ]);
            });
    }

    public static function defaultStatus(): SurveyStatus
    {
        return SurveyStatus::Draft;
    }
}

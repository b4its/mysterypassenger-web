<?php

namespace App\Filament\Schemas;

use App\Enums\EvidenceRequirement;
use App\Enums\FieldType;
use App\Enums\SurveyStatus;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Survey;
use App\Models\TemplateField;
use App\Support\AnswerTypes\AnswerTypeRegistry;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;

class SurveyFormSchemaBuilder
{
    public function __construct(private AnswerTypeRegistry $registry) {}

    public function build(Schema $schema, Survey $survey): Schema
    {
        $template = $survey->formTemplate()
            ->with([
                'sections',
                'fields.section',
                'rootGroups.questions.questionOptions',
                'rootGroups.children.questions.questionOptions',
                'rootGroups.children.children.questions.questionOptions',
                'rootGroups.children.children.children.questions.questionOptions',
            ])
            ->firstOrFail();

        $steps = [
            Wizard\Step::make('Profil Perjalanan')
                ->icon('heroicon-o-identification')
                ->schema($this->profileComponents($survey, $template)),
        ];

        foreach ($template->rootGroups as $group) {
            $steps[] = Wizard\Step::make($group->name)
                ->icon('heroicon-o-list-bullet')
                ->description($group->description)
                ->schema($this->groupComponents($group, $template));
        }

        $steps[] = Wizard\Step::make('Ringkasan')
            ->icon('heroicon-o-check-circle')
            ->schema([
                Textarea::make('summary_note')
                    ->label('Catatan Ringkasan')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);

        return $schema
            ->statePath('data')
            ->components([
                Wizard::make($steps)
                    ->columnSpanFull()
                    ->persistStepInQueryString('step')
                    ->skippable(fn () => $survey->status === SurveyStatus::Draft),
            ]);
    }

    /** @return array<int, Section> */
    private function profileComponents(Survey $survey, $template): array
    {
        $components = [
            Section::make('Identitas Survei')
                ->columns(3)
                ->schema([
                    TextInput::make('evaluator_name')
                        ->label('Nama Petugas / Evaluator')
                        ->required()
                        ->maxLength(160),

                    DateTimePicker::make('executed_at')
                        ->label('Waktu Pelaksanaan')
                        ->required()
                        ->seconds(false)
                        ->displayFormat('d/m/Y H:i'),

                    TextInput::make('location_text')
                        ->label('Lokasi')
                        ->maxLength(500)
                        ->suffixAction(
                            Action::make('geolocate')
                                ->icon('heroicon-m-map-pin')
                                ->label('Ambil Koordinat')
                                ->extraAttributes(['x-on:click' => '$dispatch("get-geolocation")']),
                        ),

                    TextInput::make('latitude')->label('Latitude')->numeric()->readOnly(),
                    TextInput::make('longitude')->label('Longitude')->numeric()->readOnly(),
                ]),
        ];

        $bySection = $template->fields->groupBy('template_section_id');

        foreach ($template->sections as $section) {
            $fields = $bySection->get($section->id, collect());

            if ($fields->isEmpty()) {
                continue;
            }

            $components[] = Section::make($section->name)
                ->description($section->description)
                ->columns($section->columns)
                ->schema($fields->map(fn (TemplateField $f) => $this->profileField($f))->all());
        }

        $orphans = $bySection->get(null, collect());

        if ($orphans->isNotEmpty()) {
            $components[] = Section::make('Informasi Tambahan')
                ->columns(2)
                ->schema($orphans->map(fn (TemplateField $f) => $this->profileField($f))->all());
        }

        return $components;
    }

    /** Satu TemplateField → satu komponen Filament. */
    private function profileField(TemplateField $field)
    {
        $name = "fields.{$field->key}";
        $rules = $this->extraRules($field);

        $component = match ($field->field_type) {
            FieldType::Textarea => Textarea::make($name)->rows(3),
            FieldType::Number => TextInput::make($name)->numeric(),
            FieldType::Date => DatePicker::make($name)->displayFormat('d/m/Y'),
            FieldType::DateTime => DateTimePicker::make($name)->seconds(false)->displayFormat('d/m/Y H:i'),
            FieldType::Select => Select::make($name)
                ->options($this->optionPairs($field))->native(false)->searchable(),
            FieldType::Radio => Radio::make($name)
                ->options($this->optionPairs($field))->inline(),
            FieldType::Checkbox => CheckboxList::make($name)
                ->options($this->optionPairs($field))->columns(2),
            FieldType::Toggle => Toggle::make($name)->inline(false),
            FieldType::File, FieldType::Image => FileUpload::make($name)
                ->disk('survey_media')
                ->directory("profile/{$field->key}")
                ->image($field->field_type === FieldType::Image)
                ->maxSize(5120),
            FieldType::Geolocation => TextInput::make($name)->placeholder('-6.2000, 106.8166'),
            default => TextInput::make($name)->maxLength(255),
        };

        return $component
            ->label($field->label)
            ->placeholder($field->placeholder)
            ->helperText($field->helper_text)
            ->required($field->is_required)
            ->rules($rules);
    }

    /** @return array<int,string> */
    private function extraRules(TemplateField $field): array
    {
        return collect($field->validation_rules ?? [])
            ->map(fn ($param, $rule) => filled($param) && ! is_numeric($rule) ? "{$rule}:{$param}" : (string) $param)
            ->values()
            ->all();
    }

    /** @return array<string,string> */
    private function optionPairs(TemplateField $field): array
    {
        return collect($field->options ?? [])
            ->mapWithKeys(fn (array $o) => [$o['value'] => $o['label']])
            ->all();
    }

    /** @return array<int, mixed> */
    private function groupComponents(QuestionGroup $group, $template): array
    {
        $components = [];

        foreach ($group->questions as $question) {
            $components[] = $this->questionBlock($question, $template);
        }

        foreach ($group->children as $child) {
            $components[] = Section::make($child->name)
                ->description($child->description)
                ->collapsible()
                ->schema($this->groupComponents($child, $template));
        }

        return $components;
    }

    /** Satu pertanyaan = input + catatan + unggah bukti, dibungkus Section. */
    private function questionBlock(Question $question, $template)
    {
        $handler = $this->registry->for($question->answer_type);
        $inner = [$handler->field($question)];

        if ($question->allow_note) {
            $inner[] = Textarea::make("answers.{$question->id}.note")
                ->label('Catatan')
                ->rows(2)
                ->columnSpanFull();
        }

        if ($question->evidence_requirement !== EvidenceRequirement::None) {
            $inner[] = FileUpload::make("answers.{$question->id}.media")
                ->label('Bukti Foto')
                ->disk('survey_media')
                ->directory(fn () => 'pending')
                ->multiple()
                ->reorderable()
                ->image()
                ->imageEditor()
                ->maxFiles(min($question->evidence_max, $template->max_evidence_per_answer))
                ->maxSize(5120)
                ->imageResizeMode('contain')
                ->imageResizeTargetWidth(1600)
                ->required($question->evidence_requirement === EvidenceRequirement::Required)
                ->helperText($question->evidence_requirement === EvidenceRequirement::Required
                    ? 'Wajib melampirkan minimal satu foto.'
                    : 'Opsional. Maksimal '.$question->evidence_max.' foto.')
                ->columnSpanFull();
        }

        $label = $question->code
            ? "{$question->code} — ".str($question->text)->limit(80)->toString()
            : str($question->text)->limit(80)->toString();

        return Section::make($label)
            ->description($question->helper_text)
            ->compact()
            ->columns(2)
            ->schema($inner)
            ->visible(fn (callable $get) => $this->passesCondition($question, $get));
    }

    /** Conditional logic: depends_on_question_id / operator / value. */
    private function passesCondition(Question $question, callable $get): bool
    {
        if (! $question->depends_on_question_id) {
            return true;
        }

        $other = $get("answers.{$question->depends_on_question_id}.value");
        $expected = $question->depends_on_value;

        return match ($question->depends_on_operator) {
            'equals' => $other == data_get($expected, 0, $expected),
            'not_equals' => $other != data_get($expected, 0, $expected),
            'in' => in_array($other, (array) $expected, false),
            'filled' => filled($other),
            'empty' => blank($other),
            default => true,
        };
    }

    /** DB → state form. Kebalikan dari SurveySubmissionService::save(). */
    public function hydrate(Survey $survey): array
    {
        $survey->load(['fieldValues', 'answers.media', 'answers.question']);

        return [
            'evaluator_name' => $survey->evaluator_name,
            'executed_at' => $survey->executed_at,
            'location_text' => $survey->location_text,
            'latitude' => $survey->latitude,
            'longitude' => $survey->longitude,
            'summary_note' => $survey->summary_note,

            'fields' => $survey->fieldValues
                ->mapWithKeys(fn ($fv) => [$fv->field_key => $fv->value])
                ->all(),

            'answers' => $survey->answers
                ->mapWithKeys(fn ($a) => [$a->question_id => [
                    'value' => $this->registry->for($a->answer_type)->toFormState($a),
                    'note' => $a->note,
                    'media' => $a->media->pluck('path', 'path')->all(),
                ]])
                ->all(),
        ];
    }
}

<?php

namespace App\Services;

use App\Enums\EvidenceRequirement;
use App\Enums\SurveyStatus;
use App\Models\Question;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAnswerMedia;
use App\Models\SurveyFieldValue;
use App\Support\AnswerTypes\AnswerTypeRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SurveySubmissionService
{
    public function __construct(
        private AnswerTypeRegistry $registry,
        private ScoreCalculator $scores,
        private SurveySnapshotService $snapshots,
    ) {}

    /**
     * Simpan state form dinamis ke tiga tabel dalam satu transaksi.
     *
     * @param  array{fields?: array<string,mixed>, answers?: array<int,array<string,mixed>>}  $state
     */
    public function save(Survey $survey, array $state, bool $submit = false): Survey
    {
        return DB::transaction(function () use ($survey, $state, $submit) {
            $this->persistScalarFields($survey, $state);

            $template = $survey->formTemplate()
                ->with(['questions.questionOptions', 'fields'])
                ->firstOrFail();

            $this->persistFields($survey, $template, $state);
            $this->persistAnswers($survey, $template, $state);

            if ($submit) {
                $survey->refresh()->load(['answers.question', 'fieldValues', 'formTemplate']);

                $this->validateForSubmit($survey);

                $survey->forceFill([
                    'status' => SurveyStatus::Submitted,
                    'submitted_at' => now(),
                ])->save();

                $survey->load(['fieldValues', 'answers.question', 'formTemplate']);
                $this->scores->recalculate($survey);
                $survey->update(['meta' => $this->snapshots->build($survey)]);
            }

            return $survey->refresh();
        });
    }

    private function persistScalarFields(Survey $survey, array $state): void
    {
        $attributes = collect([
            'evaluator_name', 'executed_at', 'location_text',
            'latitude', 'longitude', 'summary_note',
        ])
            ->filter(fn (string $key) => array_key_exists($key, $state))
            ->mapWithKeys(fn (string $key) => [$key => $state[$key]])
            ->all();

        if ($attributes !== []) {
            $survey->update($attributes);
        }
    }

    /** @param  array<string,mixed>  $state */
    private function persistFields(Survey $survey, $template, array $state): void
    {
        foreach ($template->fields as $field) {
            $raw = data_get($state, "fields.{$field->key}");

            SurveyFieldValue::updateOrCreate(
                ['survey_id' => $survey->id, 'template_field_id' => $field->id],
                [
                    'field_key' => $field->key,
                    'field_label' => $field->label,
                    'value' => $raw,
                    'value_text' => is_scalar($raw) ? (string) $raw : null,
                ],
            );
        }
    }

    /** @param  array<string,mixed>  $state */
    private function persistAnswers(Survey $survey, $template, array $state): void
    {
        foreach ($template->questions as $question) {
            $input = data_get($state, "answers.{$question->id}", []);
            $input = is_array($input) ? $input : ['value' => $input];
            $handler = $this->registry->for($question->answer_type);

            $answer = SurveyAnswer::updateOrCreate(
                ['survey_id' => $survey->id, 'question_id' => $question->id],
                array_merge(
                    $handler->toColumns($question, $input['value'] ?? null),
                    [
                        'question_group_id' => $question->question_group_id,
                        'answer_type' => $question->answer_type,
                        'note' => $input['note'] ?? null,
                        'max_score' => $question->max_score,
                        'answered_at' => now(),
                    ],
                ),
            );

            $this->syncMedia($answer, (array) ($input['media'] ?? []));
        }
    }

    /** @param  array<int|string,string>  $paths  path hasil FileUpload Filament */
    private function syncMedia(SurveyAnswer $answer, array $paths): void
    {
        $paths = array_values(array_filter($paths, fn ($p) => filled($p)));

        // Hapus media yang tidak lagi ada di state → observer menghapus file fisik.
        $answer->media()->whereNotIn('path', $paths)->get()->each->delete();

        foreach ($paths as $i => $path) {
            SurveyAnswerMedia::updateOrCreate(
                ['survey_answer_id' => $answer->id, 'path' => $path],
                ['disk' => 'survey_media', 'sort_order' => $i],
            );
        }
    }

    /**
     * Validasi lintas-field sebelum transisi ke submitted (dok. 07 §5 lapis 3).
     *
     * @throws ValidationException
     */
    private function validateForSubmit(Survey $survey): void
    {
        $errors = [];

        if ($survey->executed_at && $survey->executed_at->isFuture()) {
            $errors['executed_at'] = 'Waktu pelaksanaan tidak boleh di masa depan.';
        }

        $answers = $survey->answers->keyBy('question_id');

        foreach ($survey->formTemplate->questions as $question) {
            if (! $question->is_active) {
                continue;
            }

            $answer = $answers->get($question->id);

            if ($question->is_required && ! $this->isAnswered($question, $answer)) {
                $errors["answers.{$question->id}.value"] = "Pertanyaan wajib belum dijawab: {$question->text}";
            }

            if ($question->evidence_requirement === EvidenceRequirement::Required
                && (! $answer || $answer->media()->count() === 0)) {
                $errors["answers.{$question->id}.media"] = "Pertanyaan ini wajib melampirkan bukti: {$question->text}";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function isAnswered(Question $question, ?SurveyAnswer $answer): bool
    {
        if (! $answer) {
            return false;
        }

        return match ($question->answer_type->value) {
            'boolean' => $answer->value_boolean !== null,
            'rating', 'number' => $answer->value_number !== null,
            'select_multiple' => ! empty($answer->value_json),
            'file' => $answer->media()->count() > 0,
            default => filled($answer->value_text),
        };
    }
}

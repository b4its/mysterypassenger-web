<?php

namespace App\Services;

use App\Enums\OutputSection;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\TemplateField;
use Illuminate\Support\Collection;

/**
 * Menyusun ringkasan "apa yang akan ditanyakan" dari sebuah template formulir.
 * Dipakai oleh popup ikon (?) pada select Template Formulir, dan dapat diuji
 * terpisah dari lapisan render Blade.
 */
class TemplateQuestionPreview
{
    /**
     * @return array{
     *     name: string,
     *     version: int,
     *     transport_mode: ?string,
     *     description: ?string,
     *     fields: array<int, array{label: string, type: string, required: bool}>,
     *     groups: array<int, array<string, mixed>>
     * }
     */
    public function build(FormTemplate $template): array
    {
        $template->loadMissing([
            'transportMode',
            'fields',
            'rootGroups.questions.questionOptions',
            'rootGroups.children.questions.questionOptions',
            'rootGroups.children.children.questions.questionOptions',
        ]);

        return [
            'name' => $template->name,
            'version' => (int) $template->version,
            'transport_mode' => $template->transportMode?->name,
            'description' => $template->description,
            'fields' => $template->fields
                ->map(fn (TemplateField $field) => [
                    'label' => $field->label,
                    'type' => $field->field_type->getLabel(),
                    'required' => (bool) $field->is_required,
                ])
                ->values()
                ->all(),
            'groups' => $template->rootGroups
                ->map(fn (QuestionGroup $group) => $this->group($group))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function group(QuestionGroup $group): array
    {
        return [
            'name' => $group->name,
            'section' => $group->output_section instanceof OutputSection
                ? $group->output_section->getLabel()
                : (string) $group->output_section,
            'questions' => $group->questions
                ->map(fn (Question $question) => [
                    'text' => $question->text,
                    'code' => $question->code,
                    'type' => $question->answer_type->getLabel(),
                    'evidence_required' => $question->evidence_requirement->value === 'required',
                    'options' => $question->questionOptions
                        ->map(fn ($option) => [
                            'label' => $option->label,
                            'score' => (float) $option->score,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'children' => $group->children
                ->map(fn (QuestionGroup $child) => $this->group($child))
                ->values()
                ->all(),
        ];
    }

    /** Total pertanyaan (rekursif) — dipakai untuk badge/pengujian. */
    public function questionCount(FormTemplate $template): int
    {
        return (int) $template->questions()->count();
    }

    /** @return Collection<int, string> */
    public function allQuestionTexts(FormTemplate $template): Collection
    {
        return $template->questions()->orderBy('sort_order')->pluck('text');
    }
}

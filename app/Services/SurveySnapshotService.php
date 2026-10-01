<?php

namespace App\Services;

use App\Enums\OutputSection;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Survey;
use App\Models\TemplateField;
use App\Models\TemplateSection;

class SurveySnapshotService
{
    /**
     * Bangun snapshot struktur template saat submit → disimpan di surveys.meta.
     * Renderer PDF/print membaca ini lebih dulu agar dokumen lama tetap reproducible.
     *
     * @return array<string, mixed>
     */
    public function build(Survey $survey): array
    {
        $template = $survey->formTemplate()
            ->with([
                'sections',
                'fields',
                'rootGroups.children.children',
            ])
            ->firstOrFail();

        return [
            'captured_at' => now()->toIso8601String(),
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'version' => $template->version,
                'scoring_enabled' => $template->scoring_enabled,
                'scoring_strategy' => $template->scoring_strategy?->value,
                'passing_score' => $template->passing_score,
                'max_evidence_per_answer' => $template->max_evidence_per_answer,
            ],
            'sections' => $this->sections($template),
            'fields' => $this->fields($template),
            'groups' => $this->groups($template),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sections(FormTemplate $template): array
    {
        return $template->sections->map(fn (TemplateSection $section) => [
            'id' => $section->id,
            'name' => $section->name,
            'columns' => $section->columns,
            'sort_order' => $section->sort_order,
        ])->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function fields(FormTemplate $template): array
    {
        return $template->fields->map(fn (TemplateField $field) => [
            'id' => $field->id,
            'section_id' => $field->template_section_id,
            'key' => $field->key,
            'label' => $field->label,
            'field_type' => $field->field_type->value,
            'is_required' => $field->is_required,
            'show_in_pdf' => $field->show_in_pdf,
            'sort_order' => $field->sort_order,
        ])->values()->all();
    }

    /**
     * Struktur indikator rekursif (maksimum 3 level) dengan pertanyaan & opsinya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function groups(FormTemplate $template): array
    {
        $questionsByGroup = $template->questions()
            ->with('questionOptions')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('question_group_id');

        $build = function (QuestionGroup $group) use (&$build, $questionsByGroup): array {
            return [
                'id' => $group->id,
                'parent_id' => $group->parent_id,
                'name' => $group->name,
                'description' => $group->description,
                'output_section' => $group->output_section instanceof OutputSection
                    ? $group->output_section->value
                    : (string) $group->output_section,
                'weight' => (float) $group->weight,
                'depth' => $group->depth,
                'sort_order' => $group->sort_order,
                'questions' => $questionsByGroup
                    ->get($group->id, collect())
                    ->map(fn (Question $question) => $this->question($question))
                    ->values()
                    ->all(),
                'children' => $group->children
                    ->map(fn (QuestionGroup $child) => $build($child))
                    ->values()
                    ->all(),
            ];
        };

        return $template->rootGroups()
            ->with(['children.children'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (QuestionGroup $group) => $build($group))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function question(Question $question): array
    {
        return [
            'id' => $question->id,
            'code' => $question->code,
            'text' => $question->text,
            'helper_text' => $question->helper_text,
            'answer_type' => $question->answer_type->value,
            'options' => $question->options,
            'is_required' => $question->is_required,
            'evidence_requirement' => $question->evidence_requirement->value,
            'evidence_max' => $question->evidence_max,
            'allow_note' => $question->allow_note,
            'weight' => (float) $question->weight,
            'max_score' => (float) $question->max_score,
            'sort_order' => $question->sort_order,
            'depends_on' => $question->depends_on_question_id ? [
                'question_id' => $question->depends_on_question_id,
                'operator' => $question->depends_on_operator,
                'value' => $question->depends_on_value,
            ] : null,
            'choices' => $question->questionOptions
                ->map(fn ($option) => [
                    'value' => $option->value,
                    'label' => $option->label,
                    'score' => (float) $option->score,
                    'is_compliant' => $option->is_compliant,
                ])
                ->values()
                ->all(),
        ];
    }
}

<?php

namespace App\Services;

use App\Enums\TemplateStatus;
use App\Models\FormTemplate;
use App\Models\Question;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FormTemplateVersionService
{
    /**
     * Kloning template menjadi versi baru berstatus draf.
     * Seluruh anak dikloning dengan pemetaan ID lama→baru agar
     * depends_on_question_id tetap menunjuk pertanyaan pada versi baru.
     */
    public function createNewVersion(FormTemplate $template): FormTemplate
    {
        return DB::transaction(function () use ($template) {
            $template->load([
                'sections',
                'fields',
                'questionGroups',
                'questions.questionOptions',
            ]);

            $new = $template->replicate($this->attributesToExclude($template));
            $new->version = $this->nextVersion($template);
            $new->status = TemplateStatus::Draft;
            $new->published_at = null;
            $new->created_by = Auth::id();
            $new->save();

            // ── Sections ────────────────────────────────────────────────────
            $sectionMap = [];
            foreach ($template->sections as $section) {
                $clone = $section->replicate();
                $clone->form_template_id = $new->id;
                $clone->save();
                $sectionMap[$section->id] = $clone->id;
            }

            // ── Fields ──────────────────────────────────────────────────────
            foreach ($template->fields as $field) {
                $clone = $field->replicate();
                $clone->form_template_id = $new->id;
                $clone->template_section_id = $field->template_section_id
                    ? ($sectionMap[$field->template_section_id] ?? null)
                    : null;
                $clone->save();
            }

            // ── Question groups (pertahankan hierarki) ──────────────────────
            $groupMap = [];
            $groups = $template->questionGroups->sortBy('depth');

            foreach ($groups as $group) {
                $clone = $group->replicate();
                $clone->form_template_id = $new->id;
                $clone->parent_id = $group->parent_id
                    ? ($groupMap[$group->parent_id] ?? null)
                    : null;
                $clone->save();
                $groupMap[$group->id] = $clone->id;
            }

            // ── Questions (dengan pemetaan depends_on) ──────────────────────
            $questionMap = [];
            $pendingDepends = [];

            foreach ($template->questions as $question) {
                $clone = $question->replicate();
                $clone->form_template_id = $new->id;
                $clone->question_group_id = $groupMap[$question->question_group_id] ?? null;
                $clone->depends_on_question_id = null;
                $clone->save();

                $questionMap[$question->id] = $clone->id;

                if ($question->depends_on_question_id) {
                    $pendingDepends[$clone->id] = $question->depends_on_question_id;
                }

                foreach ($question->questionOptions as $option) {
                    $optionClone = $option->replicate();
                    $optionClone->question_id = $clone->id;
                    $optionClone->save();
                }
            }

            // Perbaiki depends_on_question_id ke pertanyaan hasil klon.
            foreach ($pendingDepends as $newQuestionId => $oldDependsId) {
                if (isset($questionMap[$oldDependsId])) {
                    Question::whereKey($newQuestionId)
                        ->update(['depends_on_question_id' => $questionMap[$oldDependsId]]);
                }
            }

            return $new->refresh();
        });
    }

    private function nextVersion(FormTemplate $template): int
    {
        return (int) FormTemplate::withTrashed()
            ->where('transport_mode_id', $template->transport_mode_id)
            ->where('slug', $template->slug)
            ->max('version') + 1;
    }

    /**
     * Kolom yang tidak boleh ikut dikloning: status/terbitan dibuat ulang,
     * dan kolom agregat dari `counts()` (mis. question_groups_count) bukan kolom nyata.
     *
     * @return array<int, string>
     */
    private function attributesToExclude(FormTemplate $template): array
    {
        $fixed = [
            'id', 'status', 'published_at', 'created_by', 'created_at', 'updated_at', 'deleted_at',
        ];

        $aggregates = array_filter(
            array_keys($template->getAttributes()),
            fn (string $key) => str_ends_with($key, '_count'),
        );

        return array_merge($fixed, array_values($aggregates));
    }
}

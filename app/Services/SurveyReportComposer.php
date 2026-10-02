<?php

namespace App\Services;

use App\Enums\AnswerType;
use App\Enums\OutputSection;
use App\Enums\SurveyStatus;
use App\Models\FormTemplate;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyFieldValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SurveyReportComposer
{
    public function __construct(private ReportSettingResolver $settings) {}

    /**
     * Ubah survei + snapshot jadi struktur siap-render.
     *
     * @return array<string, mixed>
     */
    public function compose(Survey $survey, OutputSection $section): array
    {
        $survey->loadMissing([
            'transportMode', 'formTemplate', 'surveyor',
            'fieldValues', 'answers.question.group', 'answers.question.questionOptions', 'answers.media',
        ]);

        $setting = $this->settings->resolve($survey->transportMode);
        $snapshot = $survey->meta;      // null jika masih draf → fallback ke relasi live

        return $this->composeFrom($survey, $section, $setting, $snapshot);
    }

    /**
     * Pratinjau dokumen dari sebuah template TANPA memerlukan survei nyata.
     * Membangun Survey sementara (tidak disimpan) berisi data contoh sehingga
     * admin dapat melihat hasil cetak untuk template apa pun.
     *
     * @return array<string, mixed>
     */
    public function composeForTemplate(FormTemplate $template, OutputSection $section): array
    {
        $template->loadMissing([
            'transportMode',
            'fields',
            'rootGroups.questions.questionOptions',
            'rootGroups.children.questions.questionOptions',
            'rootGroups.children.children.questions.questionOptions',
        ]);

        $survey = new Survey([
            'transport_mode_id' => $template->transport_mode_id,
            'form_template_id' => $template->id,
            'template_version' => $template->version,
            'evaluator_name' => 'Nama Evaluator (contoh)',
            'executed_at' => now(),
            'location_text' => 'Lokasi contoh',
            'status' => SurveyStatus::Draft,
            'summary_note' => 'Contoh catatan ringkasan.',
        ]);
        $survey->code = 'PRATINJAU/'.$template->version;

        // Relasi in-memory agar view tidak menyentuh DB.
        $survey->setRelation('formTemplate', $template);
        $survey->setRelation('transportMode', $template->transportMode);
        $survey->setRelation('surveyor', null);

        $survey->setRelation('fieldValues', $template->fields->map(fn ($field) => new SurveyFieldValue([
            'template_field_id' => $field->id,
            'field_key' => $field->key,
            'field_label' => $field->label,
            'value' => 'Contoh '.$field->label,
            'value_text' => 'Contoh '.$field->label,
        ])));

        $questions = collect();
        $template->rootGroups->each(function ($group) use (&$questions): void {
            $collect = function ($g) use (&$questions, &$collect): void {
                foreach ($g->questions as $q) {
                    $questions->push([$g, $q]);
                }
                foreach ($g->children as $child) {
                    $collect($child);
                }
            };
            $collect($group);
        });

        $survey->setRelation('answers', $questions->map(function (array $pair) {
            [$group, $question] = $pair;

            // Set relasi in-memory agar tidak menyentuh DB (preventLazyLoading).
            $question->setRelation('group', $group);
            $question->setRelation('questionOptions', $question->questionOptions ?? collect());

            $answer = new SurveyAnswer([
                'question_id' => $question->id,
                'question_group_id' => $group->id,
                'answer_type' => $question->answer_type,
                'max_score' => $question->max_score,
                'note' => null,
            ]);
            $answer->setRelation('question', $question);
            $answer->setRelation('media', collect());
            $this->fillDummyAnswer($answer, $question);

            return $answer;
        }));

        $setting = $this->settings->resolve($template->transportMode);

        return $this->composeFrom($survey, $section, $setting, null);
    }

    /**
     * Isi nilai contoh sesuai tipe jawaban (boolean default "iya").
     */
    private function fillDummyAnswer(SurveyAnswer $answer, $question): void
    {
        $firstOptionValue = $question->questionOptions->first()?->value;

        match ($question->answer_type) {
            AnswerType::Boolean => $answer->value_boolean = true,
            AnswerType::Rating => $answer->value_number = $question->ratingScale()['max'],
            AnswerType::Number => $answer->value_number = 0,
            AnswerType::SelectSingle => $answer->value_text = $firstOptionValue ?? 'Contoh',
            AnswerType::SelectMultiple => $answer->value_json = $firstOptionValue ? [$firstOptionValue] : [],
            AnswerType::Date => $answer->value_text = now()->toDateString(),
            AnswerType::File => $answer->value_text = null,
            default => $answer->value_text = 'Contoh jawaban.',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function composeFrom(Survey $survey, OutputSection $section, $setting, ?array $snapshot): array
    {
        return [
            'survey' => $survey,
            'setting' => $setting,
            'section' => $section,
            'title' => $section === OutputSection::Report
                ? $setting->report_title
                : $setting->checklist_title,
            'logo' => $this->logoDataUri($setting),
            'metaRows' => $this->metaRows($survey, $snapshot),
            'groups' => $this->groupRows($survey, $section, $snapshot),
            'showScores' => $setting->show_scores && (bool) $survey->formTemplate->scoring_enabled,
            'showPhotos' => $setting->show_photos,
            'signatures' => $this->signatures($survey, $setting),
            'generatedAt' => now(),
        ];
    }

    /**
     * Baris kop: label → nilai. Urutan dan isi meniru PdfGenerator.kt v1,
     * tetapi label diambil dari template (bukan hardcode).
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function metaRows(Survey $survey, ?array $snapshot): array
    {
        $rows = [[
            'label' => 'Nama Petugas / Evaluator',
            'value' => $survey->evaluator_name,
        ]];

        $pdfFields = collect($snapshot['fields'] ?? null)
            ?->where('show_in_pdf', true)
            ->pluck('key')
            ->all();

        foreach ($survey->fieldValues as $fv) {
            if (is_array($pdfFields) && $pdfFields !== [] && ! in_array($fv->field_key, $pdfFields, true)) {
                continue;
            }

            $rows[] = [
                'label' => $fv->field_label,
                'value' => $this->stringify($fv->value),
            ];
        }

        $rows[] = [
            'label' => 'Tanggal Pelaksanaan',
            'value' => $survey->executed_at?->translatedFormat('l, d - F - Y') ?? '-',
        ];

        if ($survey->location_text) {
            $rows[] = ['label' => 'Lokasi', 'value' => $survey->location_text];
        }

        $rows[] = ['label' => 'No. Dokumen', 'value' => $survey->code];

        return $rows;
    }

    /**
     * Struktur tabel: satu entri per indikator, berisi baris sub-indikator.
     * `rowspan` dihitung di sini agar Blade tetap bodoh.
     *
     * Bila snapshot (`surveys.meta`) tersedia, label/teks/urutan diambil dari
     * snapshot agar PDF lama tetap reproducible walau template sudah direvisi.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function groupRows(Survey $survey, OutputSection $section, ?array $snapshot): Collection
    {
        if (is_array($snapshot) && ! empty($snapshot['groups'])) {
            return $this->groupRowsFromSnapshot($survey, $section, $snapshot);
        }

        return $this->groupRowsFromRelations($survey, $section);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function groupRowsFromRelations(Survey $survey, OutputSection $section): Collection
    {
        $wanted = [$section->value, OutputSection::Both->value];

        return $survey->answers
            ->filter(fn (SurveyAnswer $a) => in_array($a->question->group->output_section->value, $wanted, true))
            ->groupBy(fn (SurveyAnswer $a) => $a->question->group->id)
            ->sortBy(fn (Collection $answers) => $answers->first()->question->group->sort_order)
            ->map(fn (Collection $answers) => $answers
                ->sortBy(fn (SurveyAnswer $a) => $a->question->sort_order)
                ->values())
            ->values()
            ->map(fn (Collection $answers, int $i) => [
                'no' => $i + 1,
                'name' => $answers->first()->question->group->name,
                'rowspan' => max(1, $answers->count()),
                'score' => $answers->sum(fn (SurveyAnswer $a) => (float) $a->score),
                'maxScore' => $answers->sum(fn (SurveyAnswer $a) => (float) $a->max_score),
                'rows' => $answers->map(fn (SurveyAnswer $a) => $this->rowFromAnswer(
                    $a,
                    $a->question->text,
                    $a->question->code,
                ))->all(),
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function groupRowsFromSnapshot(Survey $survey, OutputSection $section, array $snapshot): Collection
    {
        $wanted = [$section->value, OutputSection::Both->value];
        $answers = $survey->answers->keyBy('question_id');

        // Ratakan struktur group rekursif menjadi daftar berurutan.
        $flat = [];
        $walk = function (array $groups) use (&$walk, &$flat): void {
            foreach ($groups as $group) {
                $flat[] = $group;
                if (! empty($group['children'])) {
                    $walk($group['children']);
                }
            }
        };
        $walk($snapshot['groups']);

        $groups = collect($flat)
            ->filter(fn (array $g) => in_array($g['output_section'] ?? 'checklist', $wanted, true))
            ->sortBy('sort_order')
            ->map(function (array $group) use ($answers) {
                $rows = collect($group['questions'] ?? [])
                    ->sortBy('sort_order')
                    ->map(fn (array $q) => $answers->get($q['id']))
                    ->filter()
                    ->values();

                if ($rows->isEmpty()) {
                    return null;
                }

                $first = $rows->first();
                $meta = collect($group['questions'])->keyBy('id');

                return [
                    'no' => null,
                    'name' => $group['name'],
                    'rowspan' => max(1, $rows->count()),
                    'score' => $rows->sum(fn (SurveyAnswer $a) => (float) $a->score),
                    'maxScore' => $rows->sum(fn (SurveyAnswer $a) => (float) $a->max_score),
                    'rows' => $rows->map(fn (SurveyAnswer $a) => $this->rowFromAnswer(
                        $a,
                        $meta->get($a->question_id)['text'] ?? $a->question->text,
                        $meta->get($a->question_id)['code'] ?? $a->question->code,
                    ))->all(),
                ];
            })
            ->filter()
            ->values()
            ->map(fn (array $group, int $i) => array_merge($group, ['no' => $i + 1]));

        return $groups;
    }

    /** @return array<string, mixed> */
    private function rowFromAnswer(SurveyAnswer $answer, ?string $text, ?string $code): array
    {
        return [
            'text' => $text,
            'code' => $code,
            'answer' => $answer->displayValue(),
            'isBoolean' => $answer->answer_type === AnswerType::Boolean,
            'isPositive' => $answer->value_boolean === true,
            'note' => $answer->note,
            'score' => $answer->score,
            'maxScore' => $answer->max_score,
            'photos' => $answer->media->map(fn ($m) => [
                'src' => $this->mediaDataUri($m),
                'caption' => $m->caption,
            ])->all(),
        ];
    }

    /** @return array<int, array{label: string, name: string, position: ?string}> */
    private function signatures(Survey $survey, $setting): array
    {
        $blocks = $setting->signature_blocks ?: [[
            'label' => 'Petugas / Evaluator',
            'name' => $survey->evaluator_name,
            'position' => null,
        ]];

        return collect($blocks)
            ->map(fn (array $b) => [
                'label' => $b['label'] ?? '',
                'name' => ($b['name'] ?? null) ?: $survey->evaluator_name,
                'position' => $b['position'] ?? null,
            ])
            ->all();
    }

    /** Foto di-embed sebagai data URI: aman terhadap chroot & tak butuh enable_remote. */
    private function mediaDataUri($media): ?string
    {
        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path)) {
            return null;
        }

        return 'data:'.($media->mime_type ?: 'image/jpeg').';base64,'
            .base64_encode($disk->get($media->path));
    }

    private function logoDataUri($setting): ?string
    {
        if (! $setting->logo_path) {
            return null;
        }

        $disk = Storage::disk('report_assets');

        return $disk->exists($setting->logo_path)
            ? 'data:image/png;base64,'.base64_encode($disk->get($setting->logo_path))
            : null;
    }

    private function stringify(mixed $value): string
    {
        return match (true) {
            is_null($value) => '-',
            is_bool($value) => $value ? 'Ya' : 'Tidak',
            is_array($value) => implode(', ', array_map(fn ($v) => is_array($v) ? implode(', ', $v) : (string) $v, $value)),
            default => (string) $value,
        };
    }
}

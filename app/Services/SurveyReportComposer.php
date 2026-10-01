<?php

namespace App\Services;

use App\Enums\AnswerType;
use App\Enums\OutputSection;
use App\Models\Survey;
use App\Models\SurveyAnswer;
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

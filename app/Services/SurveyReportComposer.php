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
     * @return Collection<int, array<string, mixed>>
     */
    private function groupRows(Survey $survey, OutputSection $section, ?array $snapshot): Collection
    {
        $wanted = [$section->value, OutputSection::Both->value];

        return $survey->answers
            ->filter(fn (SurveyAnswer $a) => in_array($a->question->group->output_section->value, $wanted, true))
            ->sortBy([
                fn (SurveyAnswer $a) => $a->question->group->sort_order,
                fn (SurveyAnswer $a) => $a->question->sort_order,
            ])
            ->groupBy(fn (SurveyAnswer $a) => $a->question->group->id)
            ->values()
            ->map(fn (Collection $answers, int $i) => [
                'no' => $i + 1,
                'name' => $answers->first()->question->group->name,
                'rowspan' => max(1, $answers->count()),
                'score' => $answers->sum(fn (SurveyAnswer $a) => (float) $a->score),
                'maxScore' => $answers->sum(fn (SurveyAnswer $a) => (float) $a->max_score),
                'rows' => $answers->map(fn (SurveyAnswer $a) => [
                    'text' => $a->question->text,
                    'code' => $a->question->code,
                    'answer' => $a->displayValue(),
                    'isBoolean' => $a->answer_type === AnswerType::Boolean,
                    'isPositive' => $a->value_boolean === true,
                    'note' => $a->note,
                    'score' => $a->score,
                    'maxScore' => $a->max_score,
                    'photos' => $a->media->map(fn ($m) => [
                        'src' => $this->mediaDataUri($m),
                        'caption' => $m->caption,
                    ])->all(),
                ])->all(),
            ]);
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

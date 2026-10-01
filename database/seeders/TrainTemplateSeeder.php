<?php

namespace Database\Seeders;

use App\Enums\AnswerType;
use App\Enums\FieldType;
use App\Enums\OutputSection;
use App\Enums\TemplateStatus;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\QuestionOption;
use App\Models\TemplateField;
use App\Models\TemplateSection;
use App\Models\TransportMode;
use Illuminate\Database\Seeder;

class TrainTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $mode = TransportMode::updateOrCreate(
            ['slug' => 'kereta-api'],
            [
                'name' => 'Kereta Api',
                'code' => 'TRAIN',
                'description' => 'Angkutan kereta api penumpang.',
                'icon' => 'heroicon-o-map',
                'color' => 'warning',
                'sort_order' => 3,
                'is_active' => true,
            ],
        );

        if ($mode->formTemplates()->where('slug', 'mystery-passenger-kereta')->exists()) {
            return;
        }

        $template = FormTemplate::create([
            'transport_mode_id' => $mode->id,
            'name' => 'Mystery Passenger — Kereta Api',
            'slug' => 'mystery-passenger-kereta',
            'version' => 1,
            'description' => 'Template untuk moda kereta api dengan opsi berskor.',
            'status' => TemplateStatus::Published,
            'scoring_enabled' => true,
            'scoring_strategy' => 'weighted',
            'passing_score' => 80,
            'published_at' => now(),
        ]);

        $identitas = TemplateSection::create([
            'form_template_id' => $template->id,
            'name' => 'Informasi Kereta',
            'columns' => 2,
            'sort_order' => 1,
        ]);

        $rute = TemplateSection::create([
            'form_template_id' => $template->id,
            'name' => 'Rute & Stasiun',
            'columns' => 2,
            'sort_order' => 2,
        ]);

        $fields = [
            [$identitas, 'nomor_ka', 'Nomor Kereta Api', FieldType::Text, true, true],
            [$identitas, 'kelas', 'Kelas', FieldType::Select, true, true, [
                ['value' => 'ekonomi', 'label' => 'Ekonomi'],
                ['value' => 'bisnis', 'label' => 'Bisnis'],
                ['value' => 'eksekutif', 'label' => 'Eksekutif'],
            ]],
            [$rute, 'stasiun_asal', 'Stasiun Asal', FieldType::Text, true, true],
            [$rute, 'stasiun_tujuan', 'Stasiun Tujuan', FieldType::Text, true, true],
        ];

        foreach ($fields as $i => $row) {
            [$section, $key, $label, $type, $required, $inTable] = $row;
            $options = $row[6] ?? null;

            TemplateField::create([
                'form_template_id' => $template->id,
                'template_section_id' => $section->id,
                'key' => $key,
                'label' => $label,
                'field_type' => $type,
                'is_required' => $required,
                'show_in_table' => $inTable,
                'is_filterable' => $inTable,
                'show_in_pdf' => true,
                'sort_order' => $i + 1,
                'options' => $options,
            ]);
        }

        $ketepatan = QuestionGroup::create([
            'form_template_id' => $template->id,
            'name' => 'Ketepatan Waktu',
            'output_section' => OutputSection::Checklist,
            'weight' => 1,
            'sort_order' => 1,
        ]);

        // select_single dengan opsi berskor
        $question = Question::create([
            'form_template_id' => $template->id,
            'question_group_id' => $ketepatan->id,
            'code' => 'KTW-01',
            'text' => 'Ketepatan waktu keberangkatan',
            'answer_type' => AnswerType::SelectSingle,
            'evidence_requirement' => 'optional',
            'weight' => 1,
            'max_score' => 4,
            'sort_order' => 1,
        ]);

        foreach ([
            ['value' => 'sangat_tepat', 'label' => 'Sangat Tepat (0 menit)', 'score' => 4, 'is_compliant' => true],
            ['value' => 'tepat', 'label' => 'Tepat (< 5 menit)', 'score' => 3, 'is_compliant' => true],
            ['value' => 'terlambat_ringan', 'label' => 'Terlambat Ringan (5–15 menit)', 'score' => 2, 'is_compliant' => false],
            ['value' => 'terlambat', 'label' => 'Terlambat (> 15 menit)', 'score' => 1, 'is_compliant' => false],
        ] as $i => $option) {
            QuestionOption::create([
                'question_id' => $question->id,
                'value' => $option['value'],
                'label' => $option['label'],
                'score' => $option['score'],
                'is_compliant' => $option['is_compliant'],
                'sort_order' => $i + 1,
            ]);
        }

        $fasilitas = QuestionGroup::create([
            'form_template_id' => $template->id,
            'name' => 'Fasilitas',
            'output_section' => OutputSection::Checklist,
            'weight' => 1,
            'sort_order' => 2,
        ]);

        foreach ([
            'Kebersihan toilet',
            'Ketersediaan colokan listrik',
            'Kebersihan gerbong',
        ] as $i => $text) {
            Question::create([
                'form_template_id' => $template->id,
                'question_group_id' => $fasilitas->id,
                'text' => $text,
                'answer_type' => AnswerType::Boolean,
                'evidence_requirement' => 'optional',
                'weight' => 1,
                'max_score' => 1,
                'sort_order' => $i + 1,
            ]);
        }

        $laporan = QuestionGroup::create([
            'form_template_id' => $template->id,
            'name' => 'Laporan Kegiatan',
            'output_section' => OutputSection::Report,
            'weight' => 0,
            'sort_order' => 3,
        ]);

        Question::create([
            'form_template_id' => $template->id,
            'question_group_id' => $laporan->id,
            'text' => 'Uraian kondisi pelayanan kereta',
            'answer_type' => AnswerType::TextLong,
            'evidence_requirement' => 'optional',
            'evidence_max' => 5,
            'weight' => 0,
            'max_score' => 0,
            'sort_order' => 1,
        ]);
    }
}

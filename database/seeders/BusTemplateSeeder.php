<?php

namespace Database\Seeders;

use App\Enums\AnswerType;
use App\Enums\FieldType;
use App\Enums\OutputSection;
use App\Enums\TemplateStatus;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\TemplateField;
use App\Models\TemplateSection;
use App\Models\TransportMode;
use Illuminate\Database\Seeder;

class BusTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $mode = TransportMode::updateOrCreate(
            ['slug' => 'bus-akap'],
            [
                'name' => 'Bus AKAP',
                'code' => 'BUS',
                'description' => 'Angkutan Kota Antar Provinsi.',
                'icon' => 'heroicon-o-truck',
                'color' => 'success',
                'sort_order' => 2,
                'is_active' => true,
            ],
        );

        if ($mode->formTemplates()->where('slug', 'mystery-passenger-bus')->exists()) {
            return;
        }

        $template = FormTemplate::create([
            'transport_mode_id' => $mode->id,
            'name' => 'Mystery Passenger — Bus AKAP',
            'slug' => 'mystery-passenger-bus',
            'version' => 1,
            'description' => 'Template untuk moda bus antarkota antarprovinsi.',
            'status' => TemplateStatus::Published,
            'scoring_enabled' => true,
            'scoring_strategy' => 'weighted',
            'passing_score' => 70,
            'published_at' => now(),
        ]);

        $identitas = TemplateSection::create([
            'form_template_id' => $template->id,
            'name' => 'Informasi Bus & PO',
            'columns' => 2,
            'sort_order' => 1,
        ]);

        $rute = TemplateSection::create([
            'form_template_id' => $template->id,
            'name' => 'Rute Operasional',
            'columns' => 2,
            'sort_order' => 2,
        ]);

        $fields = [
            [$identitas, 'nomor_bus', 'Nomor Lambung Bus', FieldType::Text, true, true],
            [$identitas, 'po_bus', 'Perusahaan Otobus (PO)', FieldType::Text, true, true],
            [$identitas, 'jenis_layanan', 'Jenis Layanan', FieldType::Select, false, false, [
                ['value' => 'ekonomi', 'label' => 'Ekonomi'],
                ['value' => 'bisnis', 'label' => 'Bisnis (AC)'],
                ['value' => 'eksekutif', 'label' => 'Eksekutif'],
            ]],
            [$rute, 'terminal_asal', 'Terminal Asal', FieldType::Text, true, true],
            [$rute, 'terminal_tujuan', 'Terminal Tujuan', FieldType::Text, true, true],
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

        $kebersihan = QuestionGroup::create([
            'form_template_id' => $template->id,
            'name' => 'Kenyamanan & Kebersihan',
            'output_section' => OutputSection::Checklist,
            'weight' => 1,
            'sort_order' => 1,
        ]);

        foreach ([
            'Kebersihan interior kabin',
            'Fungsi pendingin udara (AC)',
            'Kondisi kursi penumpang',
        ] as $i => $text) {
            Question::create([
                'form_template_id' => $template->id,
                'question_group_id' => $kebersihan->id,
                'text' => $text,
                'answer_type' => AnswerType::Boolean,
                'evidence_requirement' => 'optional',
                'weight' => 1,
                'max_score' => 1,
                'sort_order' => $i + 1,
            ]);
        }

        // Satu pertanyaan rating skala 1–5
        $keselamatan = QuestionGroup::create([
            'form_template_id' => $template->id,
            'name' => 'Keselamatan Perjalanan',
            'output_section' => OutputSection::Checklist,
            'weight' => 1,
            'sort_order' => 2,
        ]);

        Question::create([
            'form_template_id' => $template->id,
            'question_group_id' => $keselamatan->id,
            'text' => 'Penilaian keseluruhan kenyamanan perjalanan',
            'answer_type' => AnswerType::Rating,
            'options' => ['scale' => ['min' => 1, 'max' => 5]],
            'evidence_requirement' => 'optional',
            'weight' => 2,
            'max_score' => 1,
            'sort_order' => 1,
        ]);

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
            'text' => 'Uraian temuan selama perjalanan',
            'answer_type' => AnswerType::TextLong,
            'evidence_requirement' => 'optional',
            'evidence_max' => 5,
            'weight' => 0,
            'max_score' => 0,
            'sort_order' => 1,
        ]);
    }
}

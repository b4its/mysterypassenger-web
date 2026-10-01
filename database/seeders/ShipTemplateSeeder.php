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

class ShipTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $mode = TransportMode::updateOrCreate(
            ['slug' => 'kapal-penumpang'],
            [
                'name' => 'Kapal Penumpang',
                'code' => 'SHIP',
                'description' => 'Angkutan laut penumpang antarpulau.',
                'icon' => 'heroicon-o-lifebuoy',
                'color' => 'info',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );

        if ($mode->formTemplates()->where('slug', 'mystery-passenger-kapal')->exists()) {
            return;
        }

        $template = FormTemplate::create([
            'transport_mode_id' => $mode->id,
            'name' => 'Mystery Passenger — Kapal Penumpang',
            'slug' => 'mystery-passenger-kapal',
            'version' => 1,
            'description' => 'Template SERVQUAL untuk moda kapal penumpang.',
            'status' => TemplateStatus::Published,
            'scoring_enabled' => true,
            'scoring_strategy' => 'weighted',
            'passing_score' => 75,
            'published_at' => now(),
        ]);

        // ── Bagian profil: menggantikan kolom hardcode v1 ────────────────
        $identitas = TemplateSection::create([
            'form_template_id' => $template->id,
            'name' => 'Informasi Kapal & Pemilik',
            'columns' => 2,
            'sort_order' => 1,
        ]);

        $rute = TemplateSection::create([
            'form_template_id' => $template->id,
            'name' => 'Rute & Lokasi',
            'columns' => 2,
            'sort_order' => 2,
        ]);

        $fields = [
            [$identitas, 'nama_kapal', 'Nama Kapal', FieldType::Text, true, true],
            [$identitas, 'operator', 'Perusahaan Pemilik', FieldType::Text, true, true],
            [$identitas, 'kapasitas', 'Kapasitas Penumpang', FieldType::Number, false, false],
            [$rute, 'asal', 'Pelabuhan Asal', FieldType::Text, true, true],
            [$rute, 'tujuan', 'Pelabuhan Tujuan', FieldType::Text, true, true],
            [$rute, 'kelas_tiket', 'Kelas Tiket', FieldType::Select, false, false],
        ];

        foreach ($fields as $i => [$section, $key, $label, $type, $required, $inTable]) {
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
                'options' => $type === FieldType::Select ? [
                    ['value' => 'ekonomi', 'label' => 'Ekonomi'],
                    ['value' => 'bisnis', 'label' => 'Bisnis'],
                    ['value' => 'vip', 'label' => 'VIP'],
                ] : null,
            ]);
        }

        // ── Indikator SERVQUAL + sub-indikator ───────────────────────────
        $servqual = [
            'Tangibles' => [
                'Kebersihan ruang penumpang',
                'Ketersediaan alat keselamatan (life jacket, ring buoy)',
                'Kondisi toilet',
                'Kelayakan tempat duduk',
            ],
            'Reliability' => [
                'Ketepatan waktu keberangkatan',
                'Ketepatan waktu kedatangan',
                'Kesesuaian rute dengan jadwal',
            ],
            'Responsiveness' => [
                'Kecepatan petugas merespons permintaan penumpang',
                'Ketersediaan informasi keterlambatan',
            ],
            'Assurance' => [
                'Petugas mengenakan identitas resmi',
                'Pelaksanaan safety briefing sebelum berlayar',
            ],
            'Empathy' => [
                'Keramahan petugas',
                'Ketersediaan fasilitas prioritas (lansia, disabilitas, ibu hamil)',
            ],
        ];

        $order = 0;

        foreach ($servqual as $groupName => $items) {
            $group = QuestionGroup::create([
                'form_template_id' => $template->id,
                'name' => $groupName,
                'output_section' => OutputSection::Checklist,
                'weight' => 1,
                'sort_order' => ++$order,
            ]);

            foreach ($items as $qOrder => $text) {
                Question::create([
                    'form_template_id' => $template->id,
                    'question_group_id' => $group->id,
                    'text' => $text,
                    'answer_type' => AnswerType::Boolean,
                    'evidence_requirement' => 'optional',
                    'evidence_max' => 3,
                    'weight' => 1,
                    'max_score' => 1,
                    'sort_order' => $qOrder + 1,
                ]);
            }
        }

        // ── Bagian "Laporan Kegiatan" (uraian teks) ──────────────────────
        $laporan = QuestionGroup::create([
            'form_template_id' => $template->id,
            'name' => 'Laporan Kegiatan',
            'output_section' => OutputSection::Report,
            'weight' => 0,
            'sort_order' => ++$order,
        ]);

        foreach ([
            'Uraian kondisi umum pelayanan selama perjalanan',
            'Temuan yang perlu mendapat perhatian',
            'Rekomendasi perbaikan',
        ] as $i => $text) {
            Question::create([
                'form_template_id' => $template->id,
                'question_group_id' => $laporan->id,
                'text' => $text,
                'answer_type' => AnswerType::TextLong,
                'evidence_requirement' => 'optional',
                'evidence_max' => 5,
                'weight' => 0,
                'max_score' => 0,
                'sort_order' => $i + 1,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\ReportSetting;
use Illuminate\Database\Seeder;

class ReportSettingSeeder extends Seeder
{
    public function run(): void
    {
        ReportSetting::updateOrCreate(
            ['transport_mode_id' => null],
            [
                'organization_name' => 'Kementerian Perhubungan Republik Indonesia',
                'checklist_title' => 'LEMBAR CEKLIST KEGIATAN MYSTERY PASSENGER',
                'report_title' => 'LAPORAN KEGIATAN MYSTERY PASSENGER',
                'letterhead_lines' => [
                    ['text' => 'Jalan Medan Merdeka Barat No. 8, Jakarta Pusat 10110'],
                    ['text' => 'Telepon (021) 3505000 — Laman resmi: dephub.go.id'],
                ],
                'footer_note' => 'Dokumen ini dihasilkan otomatis oleh Sistem Survei Mystery Passenger.',
                'paper_size' => 'a4',
                'orientation' => 'landscape',
                'show_photos' => true,
                'photos_per_row' => 2,
                'show_scores' => true,
                'signature_blocks' => [
                    ['label' => 'Petugas / Evaluator', 'name' => '', 'position' => 'Evaluator'],
                    ['label' => 'Koordinator Wilayah', 'name' => '', 'position' => 'Kepala Balai'],
                ],
            ],
        );
    }
}

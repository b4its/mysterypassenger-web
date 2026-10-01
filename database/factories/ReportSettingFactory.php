<?php

namespace Database\Factories;

use App\Models\ReportSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportSetting>
 */
class ReportSettingFactory extends Factory
{
    protected $model = ReportSetting::class;

    public function definition(): array
    {
        return [
            'transport_mode_id' => null,
            'organization_name' => 'Mystery Passenger',
            'checklist_title' => 'LEMBAR CEKLIST KEGIATAN MYSTERY PASSENGER',
            'report_title' => 'LAPORAN KEGIATAN MYSTERY PASSENGER',
            'logo_path' => null,
            'letterhead_lines' => null,
            'footer_note' => null,
            'paper_size' => 'a4',
            'orientation' => 'landscape',
            'show_photos' => true,
            'photos_per_row' => 2,
            'show_scores' => true,
            'signature_blocks' => null,
        ];
    }
}

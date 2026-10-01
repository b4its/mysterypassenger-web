<?php

namespace App\Models;

use Database\Factories\ReportSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSetting extends Model
{
    /** @use HasFactory<ReportSettingFactory> */
    use HasFactory;

    /**
     * Nilai default di level model agar instance yang belum disimpan
     * (mis. fallback ReportSettingResolver) tetap punya paper_size/orientation.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'organization_name' => 'Mystery Passenger',
        'checklist_title' => 'LEMBAR CEKLIST KEGIATAN MYSTERY PASSENGER',
        'report_title' => 'LAPORAN KEGIATAN MYSTERY PASSENGER',
        'paper_size' => 'a4',
        'orientation' => 'landscape',
        'show_photos' => true,
        'photos_per_row' => 2,
        'show_scores' => true,
    ];

    protected $fillable = [
        'transport_mode_id', 'organization_name', 'checklist_title', 'report_title',
        'logo_path', 'letterhead_lines', 'footer_note', 'paper_size', 'orientation',
        'show_photos', 'photos_per_row', 'show_scores', 'signature_blocks',
    ];

    protected function casts(): array
    {
        return [
            'letterhead_lines' => 'array',
            'signature_blocks' => 'array',
            'show_photos' => 'boolean',
            'show_scores' => 'boolean',
            'photos_per_row' => 'integer',
        ];
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class);
    }
}

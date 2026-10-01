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

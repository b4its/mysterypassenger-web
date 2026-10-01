<?php

namespace App\Models;

use App\Enums\TemplateStatus;
use Database\Factories\TransportModeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportMode extends Model
{
    /** @use HasFactory<TransportModeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'code', 'description', 'icon', 'color',
        'sort_order', 'is_active', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class);
    }

    public function publishedTemplates(): HasMany
    {
        return $this->formTemplates()
            ->where('status', TemplateStatus::Published)
            ->orderByDesc('version');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public function reportSetting(): HasOne
    {
        return $this->hasOne(ReportSetting::class);
    }
}

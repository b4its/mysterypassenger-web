<?php

namespace App\Models;

use Database\Factories\TemplateSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateSection extends Model
{
    /** @use HasFactory<TemplateSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'form_template_id', 'name', 'description', 'columns', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'columns' => 'integer',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(TemplateField::class)->orderBy('sort_order');
    }
}

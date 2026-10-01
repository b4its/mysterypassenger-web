<?php

namespace App\Models;

use Database\Factories\SurveyFieldValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyFieldValue extends Model
{
    /** @use HasFactory<SurveyFieldValueFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_id', 'template_field_id', 'field_key', 'field_label', 'value', 'value_text',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function templateField(): BelongsTo
    {
        return $this->belongsTo(TemplateField::class);
    }
}

<?php

namespace App\Models;

use App\Enums\AnswerType;
use App\Support\AnswerTypes\AnswerTypeRegistry;
use Database\Factories\SurveyAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyAnswer extends Model
{
    /** @use HasFactory<SurveyAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_id', 'question_id', 'question_group_id', 'answer_type',
        'value_boolean', 'value_number', 'value_text', 'value_json',
        'score', 'max_score', 'is_compliant', 'note', 'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'answer_type' => AnswerType::class,
            'value_boolean' => 'boolean',
            'value_number' => 'decimal:4',
            'value_json' => 'array',
            'is_compliant' => 'boolean',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'answered_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(QuestionGroup::class, 'question_group_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(SurveyAnswerMedia::class)->orderBy('sort_order');
    }

    /** Representasi siap-tampil, dipakai PDF, print, dan infolist. */
    public function displayValue(): string
    {
        return app(AnswerTypeRegistry::class)->for($this->answer_type)->display($this);
    }
}

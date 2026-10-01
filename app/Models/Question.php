<?php

namespace App\Models;

use App\Enums\AnswerType;
use App\Enums\EvidenceRequirement;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'form_template_id', 'question_group_id', 'code', 'text', 'helper_text',
        'answer_type', 'options', 'is_required', 'evidence_requirement', 'evidence_max',
        'allow_note', 'weight', 'max_score', 'sort_order', 'is_active',
        'depends_on_question_id', 'depends_on_operator', 'depends_on_value',
    ];

    protected function casts(): array
    {
        return [
            'answer_type' => AnswerType::class,
            'evidence_requirement' => EvidenceRequirement::class,
            'options' => 'array',
            'depends_on_value' => 'array',
            'is_required' => 'boolean',
            'allow_note' => 'boolean',
            'is_active' => 'boolean',
            'weight' => 'decimal:4',
            'max_score' => 'decimal:2',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(QuestionGroup::class, 'question_group_id');
    }

    public function questionOptions(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(self::class, 'depends_on_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    /** Nama field pada state form dinamis. */
    public function fieldName(): string
    {
        return "answers.{$this->id}.value";
    }

    /** @return array{min:int|float,max:int|float,labels:array} */
    public function ratingScale(): array
    {
        return [
            'min' => data_get($this->options, 'scale.min', 1),
            'max' => data_get($this->options, 'scale.max', 5),
            'labels' => data_get($this->options, 'scale.labels', []),
        ];
    }
}

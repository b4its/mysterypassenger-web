<?php

namespace App\Models;

use App\Observers\SurveyAnswerMediaObserver;
use Database\Factories\SurveyAnswerMediaFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(SurveyAnswerMediaObserver::class)]
class SurveyAnswerMedia extends Model
{
    /** @use HasFactory<SurveyAnswerMediaFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_answer_id', 'disk', 'path', 'original_name', 'mime_type',
        'size', 'width', 'height', 'caption', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function surveyAnswer(): BelongsTo
    {
        return $this->belongsTo(SurveyAnswer::class);
    }
}

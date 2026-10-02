<?php

namespace App\Http\Resources\Api\V2;

use App\Models\SurveyAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SurveyAnswer
 */
class SurveyAnswerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'question_code' => $this->question?->code,
            'question_text' => $this->question?->text,
            'answer_type' => $this->answer_type?->value ?? $this->answer_type,
            'value' => $this->typedValue(),
            'display_value' => $this->displayValue(),
            'score' => $this->score !== null ? (float) $this->score : null,
            'max_score' => $this->max_score !== null ? (float) $this->max_score : null,
            'is_compliant' => $this->is_compliant !== null ? (bool) $this->is_compliant : null,
            'note' => $this->note,
            'answered_at' => $this->answered_at?->toISOString(),
            'media' => SurveyAnswerMediaResource::collection($this->whenLoaded('media')),
        ];
    }
}

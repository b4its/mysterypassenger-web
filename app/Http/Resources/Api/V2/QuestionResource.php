<?php

namespace App\Http\Resources\Api\V2;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Question
 */
class QuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'text' => $this->text,
            'answer_type' => $this->answer_type?->value ?? $this->answer_type,
            'is_required' => (bool) $this->is_required,
            'evidence_requirement' => $this->evidence_requirement?->value ?? $this->evidence_requirement,
            'evidence_max' => (int) $this->evidence_max,
            'allow_note' => (bool) $this->allow_note,
            'weight' => (float) $this->weight,
            'max_score' => (float) $this->max_score,
            'sort_order' => (int) $this->sort_order,
            'options' => QuestionOptionResource::collection($this->whenLoaded('questionOptions')),
            'scale' => $this->options['scale'] ?? null,
            'depends_on' => $this->depends_on_question_id ? [
                'question_id' => $this->depends_on_question_id,
                'value' => $this->depends_on_value,
            ] : null,
        ];
    }
}

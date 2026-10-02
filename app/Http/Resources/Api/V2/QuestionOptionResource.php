<?php

namespace App\Http\Resources\Api\V2;

use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuestionOption
 */
class QuestionOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'value' => $this->value,
            'label' => $this->label,
            'score' => $this->score !== null ? (float) $this->score : null,
            'is_compliant' => $this->is_compliant !== null ? (bool) $this->is_compliant : null,
            'sort_order' => $this->sort_order,
        ];
    }
}

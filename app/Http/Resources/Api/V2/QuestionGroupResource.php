<?php

namespace App\Http\Resources\Api\V2;

use App\Models\QuestionGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuestionGroup
 */
class QuestionGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'description' => $this->description,
            'output_section' => $this->output_section?->value ?? $this->output_section,
            'weight' => (float) $this->weight,
            'sort_order' => (int) $this->sort_order,
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'children' => QuestionGroupResource::collection($this->whenLoaded('children')),
        ];
    }
}

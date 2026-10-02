<?php

namespace App\Http\Resources\Api\V2;

use App\Models\FormTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FormTemplate
 */
class FormTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'version' => (int) $this->version,
            'description' => $this->description,
            'status' => $this->status?->value ?? $this->status,
            'transport_mode' => TransportModeResource::make($this->whenLoaded('transportMode')),
            'scoring' => [
                'enabled' => (bool) $this->scoring_enabled,
                'strategy' => $this->scoring_strategy?->value ?? $this->scoring_strategy,
                'passing_score' => $this->passing_score !== null ? (float) $this->passing_score : null,
            ],
            'max_evidence_per_answer' => (int) $this->max_evidence_per_answer,
            'locale' => $this->locale,
            'sections' => $this->whenLoaded('sections', function () {
                return $this->sections->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'description' => $s->description,
                    'columns' => (int) $s->columns,
                    'sort_order' => (int) $s->sort_order,
                ]);
            }),
            'fields' => TemplateFieldResource::collection($this->whenLoaded('fields')),
            'groups' => QuestionGroupResource::collection($this->whenLoaded('rootGroups')),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

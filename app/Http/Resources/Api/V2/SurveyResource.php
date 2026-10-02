<?php

namespace App\Http\Resources\Api\V2;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Survey
 */
class SurveyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fieldsMap = [];
        if ($this->relationLoaded('fieldValues')) {
            foreach ($this->fieldValues as $fv) {
                $fieldsMap[$fv->field_key] = $fv->value;
            }
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'idempotency_key' => $this->idempotency_key,
            'code' => $this->code,
            'transport_mode_id' => $this->transport_mode_id,
            'transport_mode' => TransportModeResource::make($this->whenLoaded('transportMode')),
            'form_template_id' => $this->form_template_id,
            'form_template_name' => $this->formTemplate?->name,
            'template_version' => (int) $this->template_version,
            'user_id' => $this->user_id,
            'surveyor_name' => $this->surveyor?->name,
            'evaluator_name' => $this->evaluator_name,
            'executed_at' => $this->executed_at?->toISOString(),
            'location_text' => $this->location_text,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'status' => $this->status?->value ?? $this->status,
            'total_score' => $this->total_score !== null ? (float) $this->total_score : null,
            'max_score' => $this->max_score !== null ? (float) $this->max_score : null,
            'score_percentage' => $this->score_percentage !== null ? (float) $this->score_percentage : null,
            'is_passed' => $this->is_passed !== null ? (bool) $this->is_passed : null,
            'summary_note' => $this->summary_note,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'reviewed_by' => $this->reviewed_by,
            'reviewer_name' => $this->reviewer?->name,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'review_note' => $this->review_note,
            'fields' => $fieldsMap,
            'field_values' => SurveyFieldValueResource::collection($this->whenLoaded('fieldValues')),
            'answers' => SurveyAnswerResource::collection($this->whenLoaded('answers')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

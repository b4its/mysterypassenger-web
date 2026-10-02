<?php

namespace App\Http\Resources\Api\V2;

use App\Models\SurveyFieldValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SurveyFieldValue
 */
class SurveyFieldValueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_field_id' => $this->template_field_id,
            'field_key' => $this->field_key,
            'field_label' => $this->field_label,
            'value' => $this->value,
            'value_text' => $this->value_text,
        ];
    }
}

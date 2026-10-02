<?php

namespace App\Http\Resources\Api\V2;

use App\Models\TemplateField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TemplateField
 */
class TemplateFieldResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->template_section_id,
            'key' => $this->key,
            'label' => $this->label,
            'field_type' => $this->field_type?->value ?? $this->field_type,
            'is_required' => (bool) $this->is_required,
            'options' => $this->options,
            'validation_rules' => $this->validation_rules,
            'show_in_pdf' => (bool) $this->show_in_pdf,
            'sort_order' => $this->sort_order,
        ];
    }
}

<?php

namespace App\Support;

use App\Enums\FieldType;
use App\Models\TemplateField;
use Illuminate\Validation\Rule;

class TemplateFieldRuleBuilder
{
    /**
     * @return array<int, mixed>
     */
    public function rulesFor(TemplateField $field): array
    {
        $rules = [];

        $rules[] = match ($field->field_type) {
            FieldType::Text => 'string',
            FieldType::Textarea => 'string',
            FieldType::Number => 'numeric',
            FieldType::Date => 'date',
            FieldType::Select => 'string',
            FieldType::Boolean => 'boolean',
        };

        if ($field->field_type === FieldType::Text) {
            $rules[] = 'max:255';
        }

        if ($field->field_type === FieldType::Select && is_array($field->options) && count($field->options) > 0) {
            $options = array_map(function ($item) {
                return is_array($item) ? ($item['value'] ?? $item) : $item;
            }, $field->options);

            $rules[] = Rule::in($options);
        }

        if (is_array($field->validation_rules)) {
            foreach ($field->validation_rules as $rule) {
                if (filled($rule)) {
                    $rules[] = $rule;
                }
            }
        } elseif (is_string($field->validation_rules) && filled($field->validation_rules)) {
            $rules = array_merge($rules, explode('|', $field->validation_rules));
        }

        return $rules;
    }
}

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
            FieldType::Text, FieldType::Textarea, FieldType::Select,
            FieldType::Radio, FieldType::Geolocation => 'string',
            FieldType::Number => 'numeric',
            FieldType::Date, FieldType::DateTime => 'date',
            FieldType::Checkbox => 'array',
            FieldType::Toggle => 'boolean',
            FieldType::File, FieldType::Image => 'file',
        };

        if ($field->field_type === FieldType::Text) {
            $rules[] = 'max:255';
        }

        if ($field->field_type->hasOptions() && ! empty($field->optionPairs())) {
            $rules[] = Rule::in(array_keys($field->optionPairs()));
        }

        return array_merge($rules, $this->customRules($field));
    }

    /**
     * Aturan kustom dari kolom validation_rules. Mendukung dua format:
     * - peta key→parameter (KeyValue di Filament): ['max' => 255] → "max:255"
     * - daftar string rule: ['max:255', 'regex:/.../'] → diteruskan apa adanya
     *
     * @return array<int, string>
     */
    private function customRules(TemplateField $field): array
    {
        $rules = $field->validation_rules;

        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        if (! is_array($rules)) {
            return [];
        }

        $result = [];

        foreach ($rules as $key => $value) {
            if (is_int($key)) {
                // Daftar: nilainya sudah berupa rule lengkap.
                if (is_string($value) && filled($value)) {
                    $result[] = $value;
                }

                continue;
            }

            // Peta key→parameter.
            if (blank($value)) {
                $result[] = (string) $key;
            } elseif (is_array($value)) {
                $result[] = $key.':'.implode(',', $value);
            } else {
                $result[] = $key.':'.$value;
            }
        }

        return $result;
    }
}

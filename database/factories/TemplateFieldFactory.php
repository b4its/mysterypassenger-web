<?php

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\FormTemplate;
use App\Models\TemplateField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateField>
 */
class TemplateFieldFactory extends Factory
{
    protected $model = TemplateField::class;

    public function definition(): array
    {
        $key = 'field_'.fake()->unique()->word();

        return [
            'form_template_id' => FormTemplate::factory(),
            'template_section_id' => null,
            'key' => $key,
            'label' => ucfirst($key),
            'field_type' => FieldType::Text,
            'placeholder' => null,
            'helper_text' => null,
            'options' => null,
            'default_value' => null,
            'validation_rules' => null,
            'is_required' => false,
            'is_filterable' => false,
            'show_in_table' => false,
            'show_in_pdf' => true,
            'sort_order' => 0,
        ];
    }
}

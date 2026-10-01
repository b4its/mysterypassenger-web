<?php

namespace Database\Factories;

use App\Models\Survey;
use App\Models\SurveyFieldValue;
use App\Models\TemplateField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyFieldValue>
 */
class SurveyFieldValueFactory extends Factory
{
    protected $model = SurveyFieldValue::class;

    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'template_field_id' => TemplateField::factory(),
            'field_key' => 'field_'.fake()->unique()->word(),
            'field_label' => ucfirst(fake()->word()),
            'value' => fake()->word(),
            'value_text' => fake()->word(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\OutputSection;
use App\Models\FormTemplate;
use App\Models\QuestionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionGroup>
 */
class QuestionGroupFactory extends Factory
{
    protected $model = QuestionGroup::class;

    public function definition(): array
    {
        return [
            'form_template_id' => FormTemplate::factory(),
            'parent_id' => null,
            'name' => 'Indikator '.fake()->unique()->word(),
            'description' => null,
            'output_section' => OutputSection::Checklist,
            'weight' => 1,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function report(): static
    {
        return $this->state(['output_section' => OutputSection::Report]);
    }
}

<?php

namespace Database\Factories;

use App\Models\FormTemplate;
use App\Models\TemplateSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateSection>
 */
class TemplateSectionFactory extends Factory
{
    protected $model = TemplateSection::class;

    public function definition(): array
    {
        return [
            'form_template_id' => FormTemplate::factory(),
            'name' => 'Bagian '.fake()->unique()->word(),
            'description' => fake()->sentence(),
            'columns' => 2,
            'sort_order' => 0,
        ];
    }
}

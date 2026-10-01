<?php

namespace Database\Factories;

use App\Models\FormTemplate;
use App\Models\TemplateAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateAssignment>
 */
class TemplateAssignmentFactory extends Factory
{
    protected $model = TemplateAssignment::class;

    public function definition(): array
    {
        return [
            'form_template_id' => FormTemplate::factory(),
            'user_id' => User::factory(),
            'assigned_by' => null,
            'starts_at' => null,
            'due_at' => null,
            'notes' => null,
        ];
    }
}

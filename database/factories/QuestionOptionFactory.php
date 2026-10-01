<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionOption>
 */
class QuestionOptionFactory extends Factory
{
    protected $model = QuestionOption::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'value' => 'opt_'.fake()->unique()->word(),
            'label' => ucfirst(fake()->unique()->word()),
            'score' => 0,
            'is_compliant' => null,
            'color' => null,
            'sort_order' => 0,
        ];
    }
}

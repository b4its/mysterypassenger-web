<?php

namespace Database\Factories;

use App\Enums\AnswerType;
use App\Enums\EvidenceRequirement;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'form_template_id' => FormTemplate::factory(),
            'question_group_id' => QuestionGroup::factory(),
            'code' => null,
            'text' => fake()->sentence().'?',
            'helper_text' => null,
            'answer_type' => AnswerType::Boolean,
            'options' => null,
            'is_required' => true,
            'evidence_requirement' => EvidenceRequirement::Optional,
            'evidence_max' => 3,
            'allow_note' => true,
            'weight' => 1,
            'max_score' => 1,
            'sort_order' => 0,
            'is_active' => true,
            'depends_on_question_id' => null,
            'depends_on_operator' => null,
            'depends_on_value' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\AnswerType;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyAnswer>
 */
class SurveyAnswerFactory extends Factory
{
    protected $model = SurveyAnswer::class;

    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'question_id' => Question::factory(),
            'question_group_id' => function (array $attributes) {
                return Question::find($attributes['question_id'])?->question_group_id
                    ?? QuestionGroup::factory()->create()->id;
            },
            'answer_type' => AnswerType::Boolean,
            'value_boolean' => true,
            'value_number' => null,
            'value_text' => null,
            'value_json' => null,
            'score' => null,
            'max_score' => 1,
            'is_compliant' => null,
            'note' => null,
            'answered_at' => now(),
        ];
    }
}

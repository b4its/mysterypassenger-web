<?php

namespace Database\Factories;

use App\Enums\AnswerType;
use App\Enums\SurveyStatus;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\TransportMode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Survey>
 */
class SurveyFactory extends Factory
{
    protected $model = Survey::class;

    public function definition(): array
    {
        return [
            'idempotency_key' => null,
            'transport_mode_id' => TransportMode::factory(),
            'form_template_id' => FormTemplate::factory(),
            'template_version' => 1,
            'user_id' => User::factory(),
            'evaluator_name' => fake()->name(),
            'executed_at' => now()->subDay(),
            'location_text' => fake()->city(),
            'latitude' => null,
            'longitude' => null,
            'status' => SurveyStatus::Draft,
            'summary_note' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state([
            'status' => SurveyStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Buat jawaban boolean untuk setiap pertanyaan template.
     * Membutuhkan template dengan question groups & questions.
     */
    public function withAnswers(int $groups = 2, int $questions = 4, bool $allTrue = true): static
    {
        return $this->afterCreating(function (Survey $survey) use ($groups, $questions, $allTrue) {
            $template = $survey->formTemplate()->with(['questions'])->first();

            if (! $template) {
                return;
            }

            $template->questions->take($groups * $questions)->each(function (Question $q) use ($survey, $allTrue) {
                SurveyAnswer::factory()->create([
                    'survey_id' => $survey->id,
                    'question_id' => $q->id,
                    'question_group_id' => $q->question_group_id,
                    'answer_type' => AnswerType::Boolean,
                    'value_boolean' => $allTrue,
                    'max_score' => $q->max_score,
                ]);
            });
        });
    }
}

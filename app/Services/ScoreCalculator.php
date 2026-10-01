<?php

namespace App\Services;

use App\Models\QuestionGroup;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Support\AnswerTypes\AnswerTypeRegistry;
use Illuminate\Support\Collection;

class ScoreCalculator
{
    public function __construct(private AnswerTypeRegistry $registry) {}

    public function recalculate(Survey $survey): void
    {
        $template = $survey->formTemplate;

        if (! $template->scoring_enabled) {
            $survey->update([
                'total_score' => null, 'max_score' => null,
                'score_percentage' => null, 'is_passed' => null,
            ]);

            return;
        }

        $survey->loadMissing(['answers']);

        $answers = $survey->answers->keyBy('question_id');

        $total = 0.0;
        $max = 0.0;

        $groups = $template->rootGroups()
            ->with([
                'questions',
                'children.questions',
                'children.children.questions',
                'children.children.children.questions',
            ])
            ->get();

        foreach ($groups as $group) {
            [$groupTotal, $groupMax] = $this->scoreGroup($group, $answers);
            $total += $groupTotal;
            $max += $groupMax;
        }

        $percentage = $max > 0 ? round($total / $max * 100, 2) : null;

        $survey->update([
            'total_score' => round($total, 2),
            'max_score' => round($max, 2),
            'score_percentage' => $percentage,
            'is_passed' => $percentage !== null && $template->passing_score !== null
                ? $percentage >= (float) $template->passing_score
                : null,
        ]);
    }

    /**
     * @param  Collection<int, SurveyAnswer>  $answers
     * @return array{0: float, 1: float} [total, max]
     */
    private function scoreGroup(QuestionGroup $group, Collection $answers): array
    {
        $total = 0.0;
        $max = 0.0;

        foreach ($group->questions as $question) {
            if (! $question->answer_type->isScorable() || ! $question->is_active) {
                continue;
            }

            $answer = $answers->get($question->id);

            $ratio = $answer
                ? $this->registry->for($question->answer_type)->normalizedScore($question, $answer)
                : 0.0;

            $score = $ratio * (float) $question->max_score * (float) $question->weight;

            $answer?->update(['score' => round($score, 2)]);

            $total += $score;
            $max += (float) $question->max_score * (float) $question->weight;
        }

        foreach ($group->children as $child) {
            [$childTotal, $childMax] = $this->scoreGroup($child, $answers);
            $total += $childTotal;
            $max += $childMax;
        }

        return [$total * (float) $group->weight, $max * (float) $group->weight];
    }
}

<?php

namespace App\Support\AnswerTypes;

use App\Models\Question;
use App\Models\SurveyAnswer;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Component;

class SelectMultipleAnswerType implements AnswerTypeHandler
{
    public function field(Question $question): Component
    {
        return CheckboxList::make($question->fieldName())
            ->label($question->text)
            ->helperText($question->helper_text)
            ->options($question->questionOptions->pluck('label', 'value')->all())
            ->columns(2)
            ->bulkToggleable()
            ->required($question->is_required);
    }

    public function toColumns(Question $question, mixed $value): array
    {
        $values = array_values(array_filter((array) $value, fn ($v) => filled($v)));

        $labels = $question->questionOptions
            ->whereIn('value', $values)
            ->pluck('label')
            ->all();

        return [
            'value_boolean' => null,
            'value_number' => null,
            'value_text' => $labels === [] ? null : implode(', ', $labels),
            'value_json' => $values === [] ? null : $values,
            'is_compliant' => null,
        ];
    }

    public function toFormState(SurveyAnswer $answer): mixed
    {
        return $answer->value_json ?? [];
    }

    public function normalizedScore(Question $question, SurveyAnswer $answer): float
    {
        $totalOptionScore = (float) $question->questionOptions->sum('score');

        if ($totalOptionScore <= 0) {
            return 0.0;
        }

        $selected = (array) ($answer->value_json ?? []);

        $selectedScore = (float) $question->questionOptions
            ->whereIn('value', $selected)
            ->sum('score');

        return max(0.0, min(1.0, $selectedScore / $totalOptionScore));
    }

    public function display(SurveyAnswer $answer): string
    {
        $selected = (array) ($answer->value_json ?? []);

        if ($selected === []) {
            return '-';
        }

        $labels = $answer->question->questionOptions
            ->whereIn('value', $selected)
            ->pluck('label')
            ->all();

        return $labels === [] ? '-' : implode(', ', $labels);
    }
}

<?php

namespace App\Support\AnswerTypes;

use App\Models\Question;
use App\Models\SurveyAnswer;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;

class SelectSingleAnswerType implements AnswerTypeHandler
{
    public function field(Question $question): Component
    {
        $options = $question->questionOptions->pluck('label', 'value')->all();

        return count($options) <= 4
            ? Radio::make($question->fieldName())
                ->label($question->text)
                ->helperText($question->helper_text)
                ->options($options)
                ->inline()
                ->required($question->is_required)
            : Select::make($question->fieldName())
                ->label($question->text)
                ->helperText($question->helper_text)
                ->options($options)
                ->native(false)
                ->searchable()
                ->required($question->is_required);
    }

    public function toColumns(Question $question, mixed $value): array
    {
        $option = $question->questionOptions->firstWhere('value', $value);

        return [
            'value_boolean' => null,
            'value_number' => $option?->score,
            'value_text' => $value === null ? null : (string) $value,
            'value_json' => null,
            'is_compliant' => $option?->is_compliant,
        ];
    }

    public function toFormState(SurveyAnswer $answer): mixed
    {
        return $answer->value_text;
    }

    public function normalizedScore(Question $question, SurveyAnswer $answer): float
    {
        $maxOptionScore = (float) $question->questionOptions->max('score');

        if ($maxOptionScore <= 0) {
            return 0.0;
        }

        $score = (float) ($question->questionOptions->firstWhere('value', $answer->value_text)?->score ?? 0);

        return max(0.0, min(1.0, $score / $maxOptionScore));
    }

    public function display(SurveyAnswer $answer): string
    {
        return $answer->question->questionOptions->firstWhere('value', $answer->value_text)?->label
            ?? ($answer->value_text ?: '-');
    }
}

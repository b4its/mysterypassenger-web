<?php

namespace App\Support\AnswerTypes;

use App\Models\Question;
use App\Models\SurveyAnswer;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;

class RatingAnswerType implements AnswerTypeHandler
{
    public function field(Question $question): Component
    {
        $scale = $question->ratingScale();

        $options = collect(range($scale['min'], $scale['max']))
            ->mapWithKeys(fn (int $n) => [$n => $scale['labels'][$n] ?? (string) $n])
            ->all();

        return ToggleButtons::make($question->fieldName())
            ->label($question->text)
            ->helperText($question->helper_text)
            ->options($options)
            ->colors(array_fill_keys(array_keys($options), 'primary'))
            ->inline()
            ->grouped()
            ->required($question->is_required);
    }

    public function toColumns(Question $question, mixed $value): array
    {
        return [
            'value_boolean' => null,
            'value_number' => $value === null ? null : (float) $value,
            'value_text' => null,
            'value_json' => null,
            'is_compliant' => null,
        ];
    }

    public function toFormState(SurveyAnswer $answer): mixed
    {
        return $answer->value_number === null ? null : (float) $answer->value_number;
    }

    public function normalizedScore(Question $question, SurveyAnswer $answer): float
    {
        $scale = $question->ratingScale();
        $span = max(1, $scale['max'] - $scale['min']);

        return max(0.0, min(1.0, (((float) $answer->value_number) - $scale['min']) / $span));
    }

    public function display(SurveyAnswer $answer): string
    {
        $scale = $answer->question->ratingScale();

        if ($answer->value_number === null) {
            return '-';
        }

        $formatted = rtrim(rtrim(number_format((float) $answer->value_number, 2, ',', '.'), '0'), ',');

        return "{$formatted} / {$scale['max']}";
    }
}

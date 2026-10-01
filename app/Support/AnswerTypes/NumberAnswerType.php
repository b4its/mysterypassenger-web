<?php

namespace App\Support\AnswerTypes;

use App\Models\Question;
use App\Models\SurveyAnswer;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

class NumberAnswerType implements AnswerTypeHandler
{
    public function field(Question $question): Component
    {
        return TextInput::make($question->fieldName())
            ->label($question->text)
            ->helperText($question->helper_text)
            ->numeric()
            ->required($question->is_required);
    }

    public function toColumns(Question $question, mixed $value): array
    {
        return [
            'value_boolean' => null,
            'value_number' => $value === null ? null : (float) $value,
            'value_text' => $value === null ? null : (string) $value,
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
        return 0.0;
    }

    public function display(SurveyAnswer $answer): string
    {
        if ($answer->value_number === null) {
            return '-';
        }

        return rtrim(rtrim(number_format((float) $answer->value_number, 2, ',', '.'), '0'), ',');
    }
}

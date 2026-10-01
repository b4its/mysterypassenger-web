<?php

namespace App\Support\AnswerTypes;

use App\Models\Question;
use App\Models\SurveyAnswer;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;

class BooleanAnswerType implements AnswerTypeHandler
{
    public function field(Question $question): Component
    {
        return ToggleButtons::make($question->fieldName())
            ->label($question->text)
            ->helperText($question->helper_text)
            ->boolean('Ya', 'Tidak')
            ->grouped()
            ->inline()
            ->required($question->is_required);
    }

    public function toColumns(Question $question, mixed $value): array
    {
        return [
            'value_boolean' => $value === null ? null : (bool) $value,
            'value_number' => null,
            'value_text' => null,
            'value_json' => null,
            'is_compliant' => $value === null ? null : (bool) $value,
        ];
    }

    public function toFormState(SurveyAnswer $answer): mixed
    {
        return $answer->value_boolean;
    }

    public function normalizedScore(Question $question, SurveyAnswer $answer): float
    {
        return $answer->value_boolean ? 1.0 : 0.0;
    }

    public function display(SurveyAnswer $answer): string
    {
        return match ($answer->value_boolean) {
            true => 'YA',
            false => 'TIDAK',
            default => '-',
        };
    }
}

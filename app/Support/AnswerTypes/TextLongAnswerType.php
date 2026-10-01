<?php

namespace App\Support\AnswerTypes;

use App\Models\Question;
use App\Models\SurveyAnswer;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Component;

class TextLongAnswerType implements AnswerTypeHandler
{
    public function field(Question $question): Component
    {
        return Textarea::make($question->fieldName())
            ->label($question->text)
            ->helperText($question->helper_text)
            ->rows(4)
            ->columnSpanFull()
            ->required($question->is_required);
    }

    public function toColumns(Question $question, mixed $value): array
    {
        return [
            'value_boolean' => null,
            'value_number' => null,
            'value_text' => $value === null ? null : (string) $value,
            'value_json' => null,
            'is_compliant' => null,
        ];
    }

    public function toFormState(SurveyAnswer $answer): mixed
    {
        return $answer->value_text;
    }

    public function normalizedScore(Question $question, SurveyAnswer $answer): float
    {
        return 0.0;
    }

    public function display(SurveyAnswer $answer): string
    {
        return filled($answer->value_text) ? (string) $answer->value_text : '-';
    }
}

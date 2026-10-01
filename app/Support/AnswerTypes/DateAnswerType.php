<?php

namespace App\Support\AnswerTypes;

use App\Models\Question;
use App\Models\SurveyAnswer;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Carbon;

class DateAnswerType implements AnswerTypeHandler
{
    public function field(Question $question): Component
    {
        return DatePicker::make($question->fieldName())
            ->label($question->text)
            ->helperText($question->helper_text)
            ->displayFormat('d/m/Y')
            ->native(false)
            ->required($question->is_required);
    }

    public function toColumns(Question $question, mixed $value): array
    {
        return [
            'value_boolean' => null,
            'value_number' => null,
            'value_text' => $value === null || $value === '' ? null : (string) $value,
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
        if (blank($answer->value_text)) {
            return '-';
        }

        return Carbon::parse($answer->value_text)->translatedFormat('d F Y');
    }
}

<?php

namespace App\Support\AnswerTypes;

use App\Models\Question;
use App\Models\SurveyAnswer;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Component;

class FileAnswerType implements AnswerTypeHandler
{
    public function field(Question $question): Component
    {
        return FileUpload::make("answers.{$question->id}.media")
            ->label($question->text)
            ->helperText($question->helper_text)
            ->multiple()
            ->reorderable()
            ->maxFiles($question->evidence_max)
            ->maxSize(5120)
            ->columnSpanFull()
            ->required($question->is_required);
    }

    public function toColumns(Question $question, mixed $value): array
    {
        return [
            'value_boolean' => null,
            'value_number' => null,
            'value_text' => null,
            'value_json' => null,
            'is_compliant' => null,
        ];
    }

    public function toFormState(SurveyAnswer $answer): mixed
    {
        return null;
    }

    public function normalizedScore(Question $question, SurveyAnswer $answer): float
    {
        return 0.0;
    }

    public function display(SurveyAnswer $answer): string
    {
        $count = $answer->relationLoaded('media') ? $answer->media->count() : $answer->media()->count();

        return $count > 0 ? "{$count} lampiran" : '-';
    }
}

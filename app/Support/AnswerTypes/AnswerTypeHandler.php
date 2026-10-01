<?php

namespace App\Support\AnswerTypes;

use App\Models\Question;
use App\Models\SurveyAnswer;
use Filament\Schemas\Components\Component;

interface AnswerTypeHandler
{
    /** Komponen input Filament untuk pertanyaan ini. */
    public function field(Question $question): Component;

    /** Pemetaan nilai form → kolom survey_answers. */
    public function toColumns(Question $question, mixed $value): array;

    /** Nilai dari kolom → state form. */
    public function toFormState(SurveyAnswer $answer): mixed;

    /** 0.0–1.0, dipakai ScoreCalculator. */
    public function normalizedScore(Question $question, SurveyAnswer $answer): float;

    /** String siap tampil untuk PDF / print / infolist. */
    public function display(SurveyAnswer $answer): string;
}

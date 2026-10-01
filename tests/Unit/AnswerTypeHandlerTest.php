<?php

use App\Enums\AnswerType;
use App\Models\Question;
use App\Models\SurveyAnswer;
use App\Support\AnswerTypes\AnswerTypeHandler;
use App\Support\AnswerTypes\AnswerTypeRegistry;

dataset('handlers', [
    AnswerType::Boolean,
    AnswerType::Rating,
    AnswerType::SelectSingle,
    AnswerType::SelectMultiple,
    AnswerType::TextShort,
    AnswerType::TextLong,
    AnswerType::Number,
    AnswerType::Date,
    AnswerType::File,
]);

it('menyediakan handler untuk setiap tipe jawaban', function (AnswerType $type) {
    expect(app(AnswerTypeRegistry::class)->for($type))
        ->toBeInstanceOf(AnswerTypeHandler::class);
})->with('handlers');

it('mengembalikan kunci kolom yang lengkap dari toColumns()', function (AnswerType $type) {
    $question = Question::factory()->create(['answer_type' => $type]);

    expect(app(AnswerTypeRegistry::class)->for($type)->toColumns($question, null))
        ->toHaveKeys(['value_boolean', 'value_number', 'value_text', 'value_json', 'is_compliant']);
})->with('handlers');

it('mengembalikan YA/TIDAK untuk boolean agar PDF identik dengan v1', function () {
    $answer = SurveyAnswer::factory()->create([
        'answer_type' => AnswerType::Boolean,
        'value_boolean' => true,
    ]);

    expect($answer->displayValue())->toBe('YA');

    $answer->update(['value_boolean' => false]);

    expect($answer->refresh()->displayValue())->toBe('TIDAK');
});

it('menghasilkan skor ternormalisasi antara 0 dan 1 untuk tipe scorable', function () {
    $question = Question::factory()->create([
        'answer_type' => AnswerType::Boolean,
        'max_score' => 1,
    ]);

    $answer = SurveyAnswer::factory()->create([
        'question_id' => $question->id,
        'answer_type' => AnswerType::Boolean,
        'value_boolean' => true,
    ]);

    $handler = app(AnswerTypeRegistry::class)->for(AnswerType::Boolean);

    expect($handler->normalizedScore($question, $answer))->toBeGreaterThanOrEqual(0.0)
        ->toBeLessThanOrEqual(1.0);
});

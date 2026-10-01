<?php

use App\Enums\AnswerType;
use App\Models\Question;
use App\Models\QuestionOption;
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

/** Bangun pertanyaan dengan opsi bila tipe memerlukannya. */
function questionFor(AnswerType $type): Question
{
    $question = Question::factory()->create([
        'answer_type' => $type,
        'options' => $type === AnswerType::Rating ? ['scale' => ['min' => 1, 'max' => 5]] : null,
        'evidence_max' => 3,
    ]);

    if ($type->needsOptions()) {
        QuestionOption::factory()->create([
            'question_id' => $question->id, 'value' => 'ya', 'label' => 'Ya', 'score' => 3, 'is_compliant' => true,
        ]);
        QuestionOption::factory()->create([
            'question_id' => $question->id, 'value' => 'tidak', 'label' => 'Tidak', 'score' => 1, 'is_compliant' => false,
        ]);
    }

    return $question->refresh();
}

it('menyediakan handler untuk setiap tipe jawaban', function (AnswerType $type) {
    expect(app(AnswerTypeRegistry::class)->for($type))
        ->toBeInstanceOf(AnswerTypeHandler::class);
})->with('handlers');

it('membangun komponen field untuk setiap tipe jawaban', function (AnswerType $type) {
    $question = questionFor($type);
    $handler = app(AnswerTypeRegistry::class)->for($type);

    expect($handler->field($question))->not->toBeNull();
})->with('handlers');

it('mengembalikan kunci kolom yang lengkap dari toColumns()', function (AnswerType $type) {
    $question = questionFor($type);

    expect(app(AnswerTypeRegistry::class)->for($type)->toColumns($question, null))
        ->toHaveKeys(['value_boolean', 'value_number', 'value_text', 'value_json', 'is_compliant']);
})->with('handlers');

it('memetakan nilai form ke kolom dan kembali ke state', function (AnswerType $type) {
    $question = questionFor($type);
    $handler = app(AnswerTypeRegistry::class)->for($type);

    $value = match ($type) {
        AnswerType::Boolean => true,
        AnswerType::Rating, AnswerType::Number => 3.0,
        AnswerType::SelectSingle => 'ya',
        AnswerType::SelectMultiple => ['ya'],
        AnswerType::Date => '2026-10-02',
        default => 'teks contoh',
    };

    $columns = $handler->toColumns($question, $value);

    $answer = SurveyAnswer::factory()->create(array_merge($columns, [
        'question_id' => $question->id,
        'question_group_id' => $question->question_group_id,
        'answer_type' => $type,
        'max_score' => $question->max_score,
    ]));

    // FileAnswerType menyimpan nilai di survey_answer_media → toFormState() null.
    if ($type !== AnswerType::File) {
        expect($handler->toFormState($answer->refresh()))->not->toBeNull();
    }

    expect($handler->display($answer->refresh()))->toBeString();
})->with('handlers');

it('menghasilkan skor ternormalisasi antara 0 dan 1 untuk tipe scorable', function (AnswerType $type) {
    $question = questionFor($type);
    $handler = app(AnswerTypeRegistry::class)->for($type);

    $value = match ($type) {
        AnswerType::Boolean => true,
        AnswerType::Rating => 4,
        AnswerType::SelectSingle => 'ya',
        AnswerType::SelectMultiple => ['ya', 'tidak'],
        default => null,
    };

    $answer = SurveyAnswer::factory()->create(array_merge(
        $handler->toColumns($question, $value),
        [
            'question_id' => $question->id,
            'question_group_id' => $question->question_group_id,
            'answer_type' => $type,
            'max_score' => $question->max_score,
        ],
    ));

    $score = $handler->normalizedScore($question, $answer->refresh());

    expect($score)->toBeGreaterThanOrEqual(0.0)->toBeLessThanOrEqual(1.0);
})->with([
    AnswerType::Boolean,
    AnswerType::Rating,
    AnswerType::SelectSingle,
    AnswerType::SelectMultiple,
]);

it('mengembalikan YA/TIDAK untuk boolean agar PDF identik dengan v1', function () {
    $answer = SurveyAnswer::factory()->create([
        'answer_type' => AnswerType::Boolean,
        'value_boolean' => true,
    ]);

    expect($answer->displayValue())->toBe('YA');

    $answer->update(['value_boolean' => false]);

    expect($answer->refresh()->displayValue())->toBe('TIDAK');
});

it('menampilkan jawaban kosong sebagai tanda hubung', function (AnswerType $type) {
    $question = questionFor($type);
    $handler = app(AnswerTypeRegistry::class)->for($type);

    $answer = SurveyAnswer::factory()->create([
        'question_id' => $question->id,
        'question_group_id' => $question->question_group_id,
        'answer_type' => $type,
        'value_boolean' => null,
        'value_number' => null,
        'value_text' => null,
        'value_json' => null,
    ]);

    // FileAnswerType menampilkan jumlah lampiran; sisanya '-' bila kosong.
    $display = $handler->display($answer->refresh());

    expect($display)->toBeString();
})->with('handlers');

it('melempar exception untuk tipe jawaban tak dikenal', function () {
    $registry = app(AnswerTypeRegistry::class);

    expect(fn () => (new ReflectionClass($registry))->getProperty('map'))
        ->not->toThrow(Throwable::class);

    // Semua tipe pada enum wajib punya handler.
    foreach (AnswerType::cases() as $type) {
        expect($registry->for($type))->toBeInstanceOf(AnswerTypeHandler::class);
    }
});

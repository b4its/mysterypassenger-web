<?php

use App\Enums\AnswerType;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Services\ScoreCalculator;

it('menghitung skor boolean tanpa bobot', function () {
    $template = FormTemplate::factory()->withChecklist(1, 4)->create([
        'scoring_enabled' => true,
        'passing_score' => 50,
    ]);
    $survey = Survey::factory()->for($template)->create();

    $template->questions->each(fn (Question $q, int $i) => SurveyAnswer::factory()->create([
        'survey_id' => $survey->id,
        'question_id' => $q->id,
        'question_group_id' => $q->question_group_id,
        'answer_type' => AnswerType::Boolean,
        'value_boolean' => $i < 3,          // 3 dari 4 bernilai YA
        'max_score' => 1,
    ]));

    $survey->refresh()->load(['answers', 'formTemplate']);
    app(ScoreCalculator::class)->recalculate($survey);

    expect($survey->fresh())
        ->total_score->toEqual('3.00')
        ->max_score->toEqual('4.00')
        ->score_percentage->toEqual('75.00')
        ->is_passed->toBeTrue();
});

it('mengabaikan pertanyaan bertipe non-scorable dari skor maksimum', function () {
    $template = FormTemplate::factory()->create(['scoring_enabled' => true]);
    $group = QuestionGroup::factory()->for($template)->create();

    $boolean = Question::factory()->for($template)->for($group, 'group')->create([
        'answer_type' => AnswerType::Boolean, 'max_score' => 1, 'weight' => 1,
    ]);

    Question::factory()->for($template)->for($group, 'group')->create([
        'answer_type' => AnswerType::TextLong, 'max_score' => 10, 'weight' => 5,
    ]);

    $survey = Survey::factory()->for($template)->create();

    SurveyAnswer::factory()->create([
        'survey_id' => $survey->id,
        'question_id' => $boolean->id,
        'question_group_id' => $group->id,
        'answer_type' => AnswerType::Boolean,
        'value_boolean' => true,
        'max_score' => 1,
    ]);

    $survey->refresh()->load(['answers', 'formTemplate']);
    app(ScoreCalculator::class)->recalculate($survey);

    // max_score = 1, bukan 11 — pertanyaan uraian tidak ikut dihitung
    expect($survey->fresh())->max_score->toEqual('1.00');
});

it('menerapkan bobot indikator secara bersarang', function () {
    $template = FormTemplate::factory()->create(['scoring_enabled' => true]);

    $parent = QuestionGroup::factory()->for($template)->create(['weight' => 2, 'sort_order' => 1]);
    $child = QuestionGroup::factory()->for($template)->create([
        'parent_id' => $parent->id,
        'weight' => 3,
        'depth' => 1,
        'sort_order' => 1,
    ]);

    $question = Question::factory()->for($template)->for($child, 'group')->create([
        'answer_type' => AnswerType::Boolean, 'max_score' => 1, 'weight' => 1,
    ]);

    $survey = Survey::factory()->for($template)->create();

    SurveyAnswer::factory()->create([
        'survey_id' => $survey->id,
        'question_id' => $question->id,
        'question_group_id' => $child->id,
        'answer_type' => AnswerType::Boolean,
        'value_boolean' => true,
        'max_score' => 1,
    ]);

    $survey->refresh()->load(['answers', 'formTemplate']);
    app(ScoreCalculator::class)->recalculate($survey);

    // total = 1 × 3 (child) × 2 (parent) = 6; max = 6; persen = 100
    expect($survey->fresh())
        ->total_score->toEqual('6.00')
        ->max_score->toEqual('6.00')
        ->score_percentage->toEqual('100.00');
});

it('tidak membagi nol ketika seluruh pertanyaan non-scorable', function () {
    $template = FormTemplate::factory()->create(['scoring_enabled' => true]);
    $group = QuestionGroup::factory()->for($template)->create();

    Question::factory()->for($template)->for($group, 'group')->create([
        'answer_type' => AnswerType::TextLong, 'max_score' => 5,
    ]);

    $survey = Survey::factory()->for($template)->create();
    $survey->refresh()->load(['answers', 'formTemplate']);

    app(ScoreCalculator::class)->recalculate($survey);

    expect($survey->fresh()->score_percentage)->toBeNull();
});

it('mengembalikan skor null ketika scoring dimatikan', function () {
    $template = FormTemplate::factory()->withChecklist(1, 2)->create(['scoring_enabled' => false]);
    $survey = Survey::factory()->for($template)->create([
        'total_score' => 99,
        'score_percentage' => 99,
    ]);

    $survey->refresh()->load(['answers', 'formTemplate']);
    app(ScoreCalculator::class)->recalculate($survey);

    expect($survey->fresh())
        ->total_score->toBeNull()
        ->score_percentage->toBeNull()
        ->is_passed->toBeNull();
});

it('memberi skor 0 untuk pertanyaan wajib yang tidak dijawab', function () {
    $template = FormTemplate::factory()->withChecklist(1, 2)->create(['scoring_enabled' => true]);
    $survey = Survey::factory()->for($template)->create();

    [$q1, $q2] = $template->questions;

    SurveyAnswer::factory()->create([
        'survey_id' => $survey->id,
        'question_id' => $q1->id,
        'question_group_id' => $q1->question_group_id,
        'answer_type' => AnswerType::Boolean,
        'value_boolean' => true,
        'max_score' => 1,
    ]);

    // q2 sengaja tidak dijawab

    $survey->refresh()->load(['answers', 'formTemplate']);
    app(ScoreCalculator::class)->recalculate($survey);

    expect($survey->fresh())
        ->total_score->toEqual('1.00')
        ->max_score->toEqual('2.00')
        ->score_percentage->toEqual('50.00');
});

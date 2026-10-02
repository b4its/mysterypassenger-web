<?php

use App\Enums\SurveyStatus;
use App\Models\FormTemplate;
use App\Models\Survey;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('mengizinkan reviewer melihat antrean review dan memproses persetujuan', function () {
    $reviewer = User::factory()->reviewer()->create();
    Sanctum::actingAs($reviewer, ['survey:review', 'survey:read']);

    $template = FormTemplate::factory()->published()->withFields()->create();
    $submitted = Survey::factory()->for($template)->submitted()->create();

    // Lihat antrean
    $this->getJson(route('api.v2.review.surveys.index'))
        ->assertOk()
        ->assertJsonFragment(['id' => $submitted->id]);

    // Setujui
    $this->postJson(route('api.v2.review.surveys.transition', $submitted), [
        'status' => SurveyStatus::Approved->value,
    ])->assertOk()
        ->assertJsonPath('data.status', SurveyStatus::Approved->value);

    expect($submitted->fresh()->status)->toBe(SurveyStatus::Approved)
        ->and($submitted->fresh()->reviewed_by)->toBe($reviewer->id);
});

it('mewajibkan catatan saat reviewer menolak survei', function () {
    $reviewer = User::factory()->reviewer()->create();
    Sanctum::actingAs($reviewer, ['survey:review', 'survey:read']);

    $template = FormTemplate::factory()->published()->withFields()->create();
    $submitted = Survey::factory()->for($template)->submitted()->create();

    // Tanpa catatan -> 422
    $this->postJson(route('api.v2.review.surveys.transition', $submitted), [
        'status' => SurveyStatus::Rejected->value,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['note']);

    // Dengan catatan -> sukses
    $this->postJson(route('api.v2.review.surveys.transition', $submitted), [
        'status' => SurveyStatus::Rejected->value,
        'note' => 'Foto bukti tidak jelas pada toilet',
    ])->assertOk()
        ->assertJsonPath('data.status', SurveyStatus::Rejected->value)
        ->assertJsonPath('data.review_note', 'Foto bukti tidak jelas pada toilet');
});

it('melarang surveyor mengakses endpoint review', function () {
    $surveyor = User::factory()->surveyor()->create();
    Sanctum::actingAs($surveyor, ['survey:read', 'survey:write']);

    $this->getJson(route('api.v2.review.surveys.index'))
        ->assertForbidden();
});

it('menyertakan reviewer & jawaban pada antrean tanpa lazy loading', function () {
    $reviewer = User::factory()->reviewer()->create();
    Sanctum::actingAs($reviewer, ['survey:review', 'survey:read']);

    $template = FormTemplate::factory()->published()->withChecklist(1, 2)->create();
    // Survei sudah disetujui oleh reviewer ini (sehingga relasi `reviewer` diakses).
    $survey = Survey::factory()->for($template)->submitted()->withAnswers(1, 2)->create([
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => now(),
        'status' => SurveyStatus::Submitted,
    ]);

    $response = $this->getJson(route('api.v2.review.surveys.index'));

    $response->assertOk()
        ->assertJsonPath('data.0.reviewed_by', $reviewer->id)
        ->assertJsonPath('data.0.reviewer_name', $reviewer->name);
});

it('memuat ulang survey setelah transisi sehingga status terbaru terserialisasi', function () {
    $reviewer = User::factory()->reviewer()->create();
    Sanctum::actingAs($reviewer, ['survey:review', 'survey:read']);

    $template = FormTemplate::factory()->published()->withChecklist(1, 1)->create();
    $survey = Survey::factory()->for($template)->submitted()->withAnswers(1, 1)->create();

    $this->postJson(route('api.v2.review.surveys.transition', $survey), [
        'status' => SurveyStatus::Approved->value,
    ])->assertOk()
        ->assertJsonPath('data.status', SurveyStatus::Approved->value)
        ->assertJsonPath('data.reviewer_name', $reviewer->name)
        ->assertJsonPath('data.answers.0.question_text', fn ($v) => filled($v));
});

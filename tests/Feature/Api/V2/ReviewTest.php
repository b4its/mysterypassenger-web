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

<?php

use App\Enums\SurveyStatus;
use App\Models\Survey;
use App\Services\SurveyStateMachine;

it('mengizinkan draft ke submitted', function () {
    $survey = Survey::factory()->create(['status' => SurveyStatus::Draft]);

    $result = app(SurveyStateMachine::class)->transition($survey, SurveyStatus::Submitted);

    expect($result->status)->toBe(SurveyStatus::Submitted)
        ->and($result->submitted_at)->not->toBeNull();
});

it('menolak draft langsung ke approved', function () {
    $survey = Survey::factory()->create(['status' => SurveyStatus::Draft]);

    app(SurveyStateMachine::class)->transition($survey, SurveyStatus::Approved);
})->throws(DomainException::class);

it('menolak transisi dari approved', function () {
    $survey = Survey::factory()->create(['status' => SurveyStatus::Approved]);

    app(SurveyStateMachine::class)->transition($survey, SurveyStatus::Rejected);
})->throws(DomainException::class);

it('mewajibkan catatan saat menolak', function () {
    $survey = Survey::factory()->create(['status' => SurveyStatus::Submitted]);

    app(SurveyStateMachine::class)->transition($survey, SurveyStatus::Rejected);
})->throws(DomainException::class);

it('mencatat reviewer ketika menolak dengan catatan', function () {
    $reviewer = actingAsReviewer();
    $survey = Survey::factory()->create(['status' => SurveyStatus::Submitted]);

    $result = app(SurveyStateMachine::class)->transition($survey, SurveyStatus::Rejected, 'Bukti kurang jelas.');

    expect($result->status)->toBe(SurveyStatus::Rejected)
        ->and($result->reviewed_by)->toBe($reviewer->id)
        ->and($result->review_note)->toBe('Bukti kurang jelas.');
});

it('memperbolehkan submitted ke approved', function () {
    actingAsReviewer();
    $survey = Survey::factory()->create(['status' => SurveyStatus::Submitted]);

    $result = app(SurveyStateMachine::class)->transition($survey, SurveyStatus::Approved);

    expect($result->status)->toBe(SurveyStatus::Approved);
});

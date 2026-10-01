<?php

use App\Enums\SurveyStatus;
use App\Enums\UserRole;
use App\Models\Survey;
use App\Models\User;

dataset('matrix', [
    // [role, pemilik?, status, kemampuan, diharapkan]
    [UserRole::Admin, false, SurveyStatus::Approved, 'update', true],
    [UserRole::Admin, false, SurveyStatus::Draft, 'update', true],
    [UserRole::Reviewer, false, SurveyStatus::Submitted, 'update', true],
    [UserRole::Reviewer, false, SurveyStatus::Draft, 'update', false],
    [UserRole::Surveyor, true, SurveyStatus::Draft, 'update', true],
    [UserRole::Surveyor, true, SurveyStatus::Rejected, 'update', true],
    [UserRole::Surveyor, true, SurveyStatus::Submitted, 'update', false],
    [UserRole::Surveyor, false, SurveyStatus::Draft, 'update', false],
    [UserRole::Surveyor, true, SurveyStatus::Draft, 'delete', true],
    [UserRole::Surveyor, true, SurveyStatus::Submitted, 'delete', false],
    [UserRole::Surveyor, true, SurveyStatus::Approved, 'view', true],
    [UserRole::Surveyor, false, SurveyStatus::Approved, 'view', false],
    [UserRole::Reviewer, false, SurveyStatus::Draft, 'review', true],
    [UserRole::Surveyor, true, SurveyStatus::Submitted, 'review', false],
    [UserRole::Admin, false, SurveyStatus::Approved, 'print', true],
    [UserRole::Surveyor, false, SurveyStatus::Draft, 'print', false],
]);

it('menegakkan matriks izin survei', function (
    UserRole $role,
    bool $owner,
    SurveyStatus $status,
    string $ability,
    bool $expected,
) {
    $user = User::factory()->create(['role' => $role]);

    $survey = Survey::factory()->create([
        'status' => $status,
        'user_id' => $owner ? $user->id : User::factory()->create()->id,
    ]);

    expect($user->can($ability, $survey))->toBe($expected);
})->with('matrix');

it('mengizinkan surveyor membuat survei', function () {
    $surveyor = User::factory()->surveyor()->create();

    expect($surveyor->can('create', Survey::class))->toBeTrue();
});

it('menolak reviewer membuat survei', function () {
    $reviewer = User::factory()->reviewer()->create();

    expect($reviewer->can('create', Survey::class))->toBeFalse();
});

it('hanya admin yang boleh menghapus permanen', function () {
    $admin = User::factory()->admin()->create();
    $surveyor = User::factory()->surveyor()->create();
    $survey = Survey::factory()->create(['user_id' => $surveyor->id]);

    expect($admin->can('forceDelete', $survey))->toBeTrue()
        ->and($surveyor->can('forceDelete', $survey))->toBeFalse();
});

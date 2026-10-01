<?php

use App\Enums\SurveyStatus;
use App\Enums\UserRole;
use App\Models\FormTemplate;
use App\Models\ReportSetting;
use App\Models\Survey;
use App\Models\TransportMode;
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

dataset('mode-admin-only', [
    'create' => 'create',
    'update' => 'update',
    'delete' => 'delete',
]);

it('membatasi CRUD TransportMode hanya untuk admin', function (string $ability) {
    $admin = User::factory()->admin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $surveyor = User::factory()->surveyor()->create();
    $mode = TransportMode::factory()->create();

    $target = $ability === 'create' ? TransportMode::class : $mode;

    expect($admin->can($ability, $target))->toBeTrue()
        ->and($reviewer->can($ability, $target))->toBeFalse()
        ->and($surveyor->can($ability, $target))->toBeFalse();
})->with('mode-admin-only');

it('reviewer boleh melihat daftar moda tetapi tidak mengubahnya', function () {
    $reviewer = User::factory()->reviewer()->create();
    $mode = TransportMode::factory()->create();

    expect($reviewer->can('viewAny', TransportMode::class))->toBeTrue()
        ->and($reviewer->can('view', $mode))->toBeTrue()
        ->and($reviewer->can('update', $mode))->toBeFalse();
});

it('hanya admin yang boleh CRUD FormTemplate dan hanya saat draf', function () {
    $admin = User::factory()->admin()->create();
    $reviewer = User::factory()->reviewer()->create();

    $draft = FormTemplate::factory()->create();
    $published = FormTemplate::factory()->published()->create();

    expect($admin->can('create', FormTemplate::class))->toBeTrue()
        ->and($admin->can('update', $draft))->toBeTrue()
        ->and($admin->can('update', $published))->toBeFalse()
        ->and($reviewer->can('create', FormTemplate::class))->toBeFalse()
        ->and($reviewer->can('view', $published))->toBeTrue();
});

it('tidak mengizinkan hapus template yang sudah dipakai survei', function () {
    $admin = User::factory()->admin()->create();
    $template = FormTemplate::factory()->create();
    Survey::factory()->for($template)->create();

    expect($admin->can('delete', $template))->toBeFalse();
});

it('membatasi ReportSetting hanya untuk admin', function () {
    $admin = User::factory()->admin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $setting = ReportSetting::factory()->create();

    expect($admin->can('viewAny', ReportSetting::class))->toBeTrue()
        ->and($admin->can('update', $setting))->toBeTrue()
        ->and($reviewer->can('viewAny', ReportSetting::class))->toBeFalse()
        ->and($reviewer->can('update', $setting))->toBeFalse();
});

it('membatasi UserResource hanya untuk admin kecuali profil sendiri', function () {
    $admin = User::factory()->admin()->create();
    $reviewer = User::factory()->reviewer()->create();

    expect($admin->can('viewAny', User::class))->toBeTrue()
        ->and($admin->can('update', $reviewer))->toBeTrue()
        ->and($reviewer->can('viewAny', User::class))->toBeFalse()
        ->and($reviewer->can('update', $reviewer))->toBeTrue()
        ->and($admin->can('delete', $reviewer))->toBeTrue()
        ->and($admin->can('delete', $admin))->toBeFalse();
});

it('membatasi createFrom template untuk surveyor yang ditugaskan', function () {
    $admin = User::factory()->admin()->create();
    $surveyor = User::factory()->surveyor()->create();
    $otherSurveyor = User::factory()->surveyor()->create();

    $template = FormTemplate::factory()->published()->create();
    $template->assignedUsers()->attach($surveyor);

    expect($admin->can('createFrom', [Survey::class, $template]))->toBeTrue()
        ->and($surveyor->can('createFrom', [Survey::class, $template]))->toBeTrue()
        ->and($otherSurveyor->can('createFrom', [Survey::class, $template]))->toBeFalse();
});

it('mengizinkan createFrom bila template tidak punya penugasan', function () {
    $surveyor = User::factory()->surveyor()->create();
    $template = FormTemplate::factory()->published()->create();

    expect($surveyor->can('createFrom', [Survey::class, $template]))->toBeTrue();
});

it('hanya admin yang boleh restore survei terhapus', function () {
    $admin = User::factory()->admin()->create();
    $surveyor = User::factory()->surveyor()->create();
    $survey = Survey::factory()->create(['user_id' => $surveyor->id]);

    expect($admin->can('restore', $survey))->toBeTrue()
        ->and($surveyor->can('restore', $survey))->toBeFalse();
});

<?php

use App\Enums\AnswerType;
use App\Enums\EvidenceRequirement;
use App\Enums\FieldType;
use App\Enums\OutputSection;
use App\Enums\ScoringStrategy;
use App\Enums\SurveyStatus;
use App\Enums\TemplateStatus;
use App\Enums\UserRole;

it('memberi label & warna untuk setiap peran', function () {
    foreach (UserRole::cases() as $role) {
        expect($role->getLabel())->toBeString()->not->toBeEmpty()
            ->and($role->getColor())->toBeString()->not->toBeEmpty();
    }
});

it('menentukan sifat tipe jawaban', function () {
    expect(AnswerType::Boolean->isScorable())->toBeTrue()
        ->and(AnswerType::Rating->isScorable())->toBeTrue()
        ->and(AnswerType::SelectSingle->isScorable())->toBeTrue()
        ->and(AnswerType::SelectMultiple->isScorable())->toBeTrue()
        ->and(AnswerType::TextLong->isScorable())->toBeFalse()
        ->and(AnswerType::SelectSingle->needsOptions())->toBeTrue()
        ->and(AnswerType::Boolean->needsOptions())->toBeFalse()
        ->and(AnswerType::File->isFile())->toBeTrue()
        ->and(AnswerType::Boolean->isFile())->toBeFalse();

    foreach (AnswerType::cases() as $type) {
        expect($type->getLabel())->toBeString()->not->toBeEmpty();
    }
});

it('memberi label pada semua tipe field dan mendeteksi opsi', function () {
    expect(FieldType::Select->hasOptions())->toBeTrue()
        ->and(FieldType::Radio->hasOptions())->toBeTrue()
        ->and(FieldType::Checkbox->hasOptions())->toBeTrue()
        ->and(FieldType::Text->hasOptions())->toBeFalse();

    foreach (FieldType::cases() as $type) {
        expect($type->getLabel())->toBeString()->not->toBeEmpty();
    }
});

it('memberi label pada bagian keluaran', function () {
    expect(OutputSection::Checklist->getLabel())->toBeString()
        ->and(OutputSection::Both->getColor())->toBeString();

    foreach (OutputSection::cases() as $section) {
        expect($section->getLabel())->toBeString()->not->toBeEmpty();
    }
});

it('menentukan status template dapat diedit', function () {
    expect(TemplateStatus::Draft->isEditable())->toBeTrue()
        ->and(TemplateStatus::Published->isEditable())->toBeFalse()
        ->and(TemplateStatus::Archived->isEditable())->toBeFalse();

    foreach (TemplateStatus::cases() as $status) {
        expect($status->getLabel())->toBeString()->not->toBeEmpty()
            ->and($status->getColor())->toBeString();
    }
});

it('menegakkan aturan transisi status survei', function () {
    expect(SurveyStatus::Draft->allowedTransitions())->toBe([SurveyStatus::Submitted])
        ->and(SurveyStatus::Approved->allowedTransitions())->toBe([])
        ->and(SurveyStatus::Submitted->allowedTransitions())
        ->toContain(SurveyStatus::UnderReview, SurveyStatus::Approved, SurveyStatus::Rejected);

    expect(SurveyStatus::Draft->isEditableBySurveyor())->toBeTrue()
        ->and(SurveyStatus::Rejected->isEditableBySurveyor())->toBeTrue()
        ->and(SurveyStatus::Submitted->isEditableBySurveyor())->toBeFalse();

    expect(SurveyStatus::Submitted->isLocked())->toBeTrue()
        ->and(SurveyStatus::Approved->isLocked())->toBeTrue()
        ->and(SurveyStatus::Draft->isLocked())->toBeFalse();

    foreach (SurveyStatus::cases() as $status) {
        expect($status->getLabel())->toBeString()->not->toBeEmpty()
            ->and($status->getColor())->toBeString()
            ->and($status->getIcon())->toBeString();
    }
});

it('memberi label pada kebutuhan bukti dan strategi skor', function () {
    foreach (EvidenceRequirement::cases() as $req) {
        expect($req->getLabel())->toBeString()->not->toBeEmpty()
            ->and($req->getColor())->toBeString();
    }

    foreach (ScoringStrategy::cases() as $strategy) {
        expect($strategy->getLabel())->toBeString()->not->toBeEmpty();
    }
});

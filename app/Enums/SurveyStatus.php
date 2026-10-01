<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SurveyStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Terkirim',
            self::UnderReview => 'Sedang Direview',
            self::Approved => 'Disetujui',
            self::Rejected => 'Dikembalikan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::UnderReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil-square',
            self::Submitted => 'heroicon-o-paper-airplane',
            self::UnderReview => 'heroicon-o-magnifying-glass',
            self::Approved => 'heroicon-o-check-badge',
            self::Rejected => 'heroicon-o-arrow-uturn-left',
        };
    }

    public function isEditableBySurveyor(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function isLocked(): bool
    {
        return in_array($this, [self::Submitted, self::UnderReview, self::Approved], true);
    }

    /** @return array<SurveyStatus> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::UnderReview, self::Approved, self::Rejected],
            self::UnderReview => [self::Approved, self::Rejected],
            self::Rejected => [self::Submitted],
            self::Approved => [],
        };
    }
}

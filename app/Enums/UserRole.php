<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Reviewer = 'reviewer';
    case Surveyor = 'surveyor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Reviewer => 'Reviewer',
            self::Surveyor => 'Surveyor',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Reviewer => 'warning',
            self::Surveyor => 'success',
        };
    }
}

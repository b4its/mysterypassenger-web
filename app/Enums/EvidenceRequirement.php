<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EvidenceRequirement: string implements HasColor, HasLabel
{
    case None = 'none';
    case Optional = 'optional';
    case Required = 'required';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'Tidak Perlu',
            self::Optional => 'Opsional',
            self::Required => 'Wajib',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::Optional => 'info',
            self::Required => 'danger',
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OutputSection: string implements HasColor, HasLabel
{
    case Checklist = 'checklist';
    case Report = 'report';
    case Both = 'both';

    public function getLabel(): string
    {
        return match ($this) {
            self::Checklist => 'Lembar Ceklist',
            self::Report => 'Laporan Kegiatan',
            self::Both => 'Keduanya',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Checklist => 'info',
            self::Report => 'warning',
            self::Both => 'gray',
        };
    }
}

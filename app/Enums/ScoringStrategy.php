<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ScoringStrategy: string implements HasLabel
{
    case Weighted = 'weighted';
    case Simple = 'simple';
    case None = 'none';

    public function getLabel(): string
    {
        return match ($this) {
            self::Weighted => 'Berbobot',
            self::Simple => 'Sederhana',
            self::None => 'Tanpa Skor',
        };
    }
}

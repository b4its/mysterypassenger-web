<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FieldType: string implements HasLabel
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Date = 'date';
    case DateTime = 'datetime';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Toggle = 'toggle';
    case File = 'file';
    case Image = 'image';
    case Geolocation = 'geolocation';

    public function getLabel(): string
    {
        return match ($this) {
            self::Text => 'Teks',
            self::Textarea => 'Teks Panjang',
            self::Number => 'Angka',
            self::Date => 'Tanggal',
            self::DateTime => 'Tanggal & Waktu',
            self::Select => 'Dropdown',
            self::Radio => 'Pilihan Tunggal',
            self::Checkbox => 'Pilihan Ganda',
            self::Toggle => 'Ya / Tidak',
            self::File => 'Berkas',
            self::Image => 'Gambar',
            self::Geolocation => 'Koordinat',
        };
    }

    /** Apakah opsi (value/label) relevan untuk tipe ini. */
    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Radio, self::Checkbox], true);
    }
}

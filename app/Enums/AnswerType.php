<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AnswerType: string implements HasLabel
{
    case Boolean = 'boolean';
    case Rating = 'rating';
    case SelectSingle = 'select_single';
    case SelectMultiple = 'select_multiple';
    case TextShort = 'text_short';
    case TextLong = 'text_long';
    case Number = 'number';
    case Date = 'date';
    case File = 'file';
    case SectionNote = 'section_note';

    public function getLabel(): string
    {
        return match ($this) {
            self::Boolean => 'Ya / Tidak',
            self::Rating => 'Skala Penilaian',
            self::SelectSingle => 'Pilihan Tunggal',
            self::SelectMultiple => 'Pilihan Ganda',
            self::TextShort => 'Teks Singkat',
            self::TextLong => 'Uraian Panjang',
            self::Number => 'Angka',
            self::Date => 'Tanggal',
            self::File => 'Lampiran',
            self::SectionNote => 'Catatan Bagian',
        };
    }

    /** Tipe yang ikut dihitung dalam skor. */
    public function isScorable(): bool
    {
        return in_array($this, [
            self::Boolean, self::Rating, self::SelectSingle, self::SelectMultiple,
        ], true);
    }

    /** Apakah tipe ini memerlukan question_options. */
    public function needsOptions(): bool
    {
        return in_array($this, [self::SelectSingle, self::SelectMultiple], true);
    }

    /** Tipe yang nilainya disimpan sebagai kumpulan berkas di survey_answer_media. */
    public function isFile(): bool
    {
        return $this === self::File;
    }
}

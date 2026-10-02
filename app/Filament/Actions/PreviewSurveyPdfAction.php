<?php

namespace App\Filament\Actions;

use App\Enums\OutputSection;
use App\Models\Survey;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;

/**
 * Aksi pratinjau PDF yang menampilkan modal berisi <iframe> PDF (bukan unduhan).
 * Dipakai bersama oleh ViewSurvey dan SurveysTable agar konsisten.
 */
class PreviewSurveyPdfAction
{
    public static function make(OutputSection $section): Action
    {
        $label = $section === OutputSection::Report ? 'Pratinjau PDF Laporan' : 'Pratinjau PDF Ceklist';

        return Action::make("previewPdf{$section->value}")
            ->label($label)
            ->icon('heroicon-o-document-magnifying-glass')
            ->color('gray')
            ->modalHeading(fn (Survey $record) => ($section === OutputSection::Report
                ? 'Laporan Kegiatan'
                : 'Lembar Ceklist')." — {$record->code}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalContent(fn (Survey $record) => view('filament.partials.pdf-preview', [
                'url' => route('surveys.pdf', ['survey' => $record, 'section' => $section->value]),
                'filename' => str($record->code)->replace('/', '-')->append('-', $section->value, '.pdf')->toString(),
            ]));
    }
}

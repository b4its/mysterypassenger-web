<?php

namespace App\Filament\Resources\ReportSettings\Pages;

use App\Enums\OutputSection;
use App\Filament\Resources\ReportSettings\ReportSettingResource;
use App\Models\Survey;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;

class EditReportSetting extends EditRecord
{
    protected static string $resource = ReportSettingResource::class;

    public function getTitle(): string
    {
        return $this->record->transportMode
            ? "Pengaturan Cetak — {$this->record->transportMode->name}"
            : 'Pengaturan Cetak — Global';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Pratinjau PDF')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('gray')
                ->modalHeading('Pratinjau PDF')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalWidth(Width::SevenExtraLarge)
                ->modalContent(fn () => $this->previewContent()),
        ];
    }

    /**
     * Pratinjau memakai survei terbaru pada moda ini sebagai data nyata.
     */
    private function previewContent(): View
    {
        $survey = Survey::query()
            ->when(
                $this->record->transport_mode_id,
                fn ($q) => $q->where('transport_mode_id', $this->record->transport_mode_id),
            )
            ->latest('executed_at')
            ->first();

        if (! $survey) {
            return view('filament.partials.pdf-preview-empty');
        }

        return view('filament.partials.pdf-preview', [
            'url' => route('surveys.pdf', ['survey' => $survey, 'section' => OutputSection::Checklist->value]),
            'filename' => str($survey->code)->replace('/', '-')->append('-checklist.pdf')->toString(),
        ]);
    }
}

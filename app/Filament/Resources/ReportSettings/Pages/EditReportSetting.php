<?php

namespace App\Filament\Resources\ReportSettings\Pages;

use App\Filament\Resources\ReportSettings\ReportSettingResource;
use App\Models\Survey;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

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
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->action(function () {
                    // Cari survei terbaru pada moda ini sebagai bahan pratinjau.
                    $survey = Survey::query()
                        ->when(
                            $this->record->transport_mode_id,
                            fn ($q) => $q->where('transport_mode_id', $this->record->transport_mode_id),
                        )
                        ->latest('executed_at')
                        ->first();

                    if (! $survey) {
                        Notification::make()
                            ->title('Belum ada survei untuk pratinjau')
                            ->body('Pratinjau memakai data survei nyata. Buat satu survei pada moda terkait terlebih dahulu.')
                            ->info()
                            ->send();

                        return null;
                    }

                    return redirect()->to(route('surveys.print', [
                        'survey' => $survey,
                        'section' => 'checklist',
                        'auto' => 0,
                    ]));
                }),
        ];
    }
}

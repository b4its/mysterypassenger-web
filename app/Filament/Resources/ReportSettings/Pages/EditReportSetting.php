<?php

namespace App\Filament\Resources\ReportSettings\Pages;

use App\Filament\Resources\ReportSettings\ReportSettingResource;
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
                    Notification::make()
                        ->title('Simpan perubahan terlebih dahulu')
                        ->body('Pratinjau PDF memakai data survei nyata. Buat satu survei pada moda terkait untuk melihat hasilnya.')
                        ->info()
                        ->send();
                }),
        ];
    }
}

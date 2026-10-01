<?php

namespace App\Filament\Resources\ReportSettings\Pages;

use App\Filament\Resources\ReportSettings\ReportSettingResource;
use App\Filament\Resources\TransportModes\TransportModeResource;
use App\Models\ReportSetting;
use App\Models\TransportMode;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditReportSettingForMode extends EditRecord
{
    protected static string $resource = ReportSettingResource::class;

    protected static ?string $title = 'Pengaturan Cetak per Moda';

    public function mount(int|string $mode): void
    {
        $transportMode = TransportMode::findOrFail($mode);

        $this->record = ReportSetting::firstOrCreate(
            ['transport_mode_id' => $transportMode->id],
            [
                'organization_name' => ReportSetting::whereNull('transport_mode_id')->value('organization_name')
                    ?? 'Mystery Passenger',
            ],
        );

        $this->authorizeAccess();

        $this->fillForm();
    }

    public function getTitle(): string
    {
        return "Pengaturan Cetak — {$this->record->transportMode?->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke Moda')
                ->color('gray')
                ->url(TransportModeResource::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ReportSettingResource::getUrl('edit-for-mode', ['mode' => $this->record->transport_mode_id]);
    }
}

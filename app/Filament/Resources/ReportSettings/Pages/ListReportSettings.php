<?php

namespace App\Filament\Resources\ReportSettings\Pages;

use App\Filament\Resources\ReportSettings\ReportSettingResource;
use App\Models\ReportSetting;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListReportSettings extends ListRecords
{
    protected static string $resource = ReportSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageGlobal')
                ->label('Pengaturan Global')
                ->icon('heroicon-o-globe-alt')
                ->action(function () {
                    $global = ReportSetting::firstOrCreate(['transport_mode_id' => null]);

                    return redirect(ReportSettingResource::getUrl('edit', ['record' => $global]));
                }),
        ];
    }
}

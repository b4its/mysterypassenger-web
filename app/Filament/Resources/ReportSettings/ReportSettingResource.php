<?php

namespace App\Filament\Resources\ReportSettings;

use App\Filament\Resources\ReportSettings\Pages\EditReportSetting;
use App\Filament\Resources\ReportSettings\Pages\EditReportSettingForMode;
use App\Filament\Resources\ReportSettings\Pages\ListReportSettings;
use App\Filament\Resources\ReportSettings\Schemas\ReportSettingForm;
use App\Filament\Resources\ReportSettings\Tables\ReportSettingsTable;
use App\Models\ReportSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReportSettingResource extends Resource
{
    protected static ?string $model = ReportSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static string|\UnitEnum|null $navigationGroup = 'Konfigurasi Formulir';

    protected static ?string $navigationLabel = 'Pengaturan Cetak';

    protected static ?string $modelLabel = 'Pengaturan Cetak';

    protected static ?string $pluralModelLabel = 'Pengaturan Cetak';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return ReportSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportSettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportSettings::route('/'),
            'edit' => EditReportSetting::route('/{record}/edit'),
            'edit-for-mode' => EditReportSettingForMode::route('/mode/{mode}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}

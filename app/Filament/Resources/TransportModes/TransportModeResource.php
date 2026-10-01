<?php

namespace App\Filament\Resources\TransportModes;

use App\Filament\Resources\TransportModes\Pages\CreateTransportMode;
use App\Filament\Resources\TransportModes\Pages\EditTransportMode;
use App\Filament\Resources\TransportModes\Pages\ListTransportModes;
use App\Filament\Resources\TransportModes\Schemas\TransportModeForm;
use App\Filament\Resources\TransportModes\Tables\TransportModesTable;
use App\Models\TransportMode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransportModeResource extends Resource
{
    protected static ?string $model = TransportMode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Konfigurasi Formulir';

    protected static ?string $navigationLabel = 'Jenis Transportasi';

    protected static ?string $modelLabel = 'Jenis Transportasi';

    protected static ?string $pluralModelLabel = 'Jenis Transportasi';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TransportModeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransportModesTable::configure($table);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isAdmin() || $user->isReviewer());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransportModes::route('/'),
            'create' => CreateTransportMode::route('/create'),
            'edit' => EditTransportMode::route('/{record}/edit'),
        ];
    }
}

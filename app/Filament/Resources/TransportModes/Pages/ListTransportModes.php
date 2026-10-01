<?php

namespace App\Filament\Resources\TransportModes\Pages;

use App\Filament\Resources\TransportModes\TransportModeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransportModes extends ListRecords
{
    protected static string $resource = TransportModeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\ReportSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transportMode.name')->label('Moda')
                    ->badge()->placeholder('Global (semua moda)'),
                TextColumn::make('organization_name')->label('Instansi')->searchable(),
                TextColumn::make('paper_size')->label('Kertas')->badge(),
                TextColumn::make('orientation')->label('Orientasi')->badge(),
                IconColumn::make('show_photos')->label('Foto')->boolean(),
                IconColumn::make('show_scores')->label('Skor')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}

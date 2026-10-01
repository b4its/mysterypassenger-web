<?php

namespace App\Filament\Resources\TransportModes\Tables;

use App\Filament\Resources\ReportSettings\ReportSettingResource;
use App\Models\TransportMode;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TransportModesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                IconColumn::make('icon')->label('')->icon(fn (TransportMode $record) => $record->icon),
                TextColumn::make('name')->label('Moda')->searchable()->sortable()
                    ->description(fn (TransportMode $record) => $record->description),
                TextColumn::make('code')->label('Kode')->badge(),
                TextColumn::make('form_templates_count')->counts('formTemplates')->label('Template')->badge(),
                TextColumn::make('surveys_count')->counts('surveys')->label('Survei')->badge()->color('info'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Status Aktif'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('reportSetting')
                    ->label('Pengaturan Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (TransportMode $record) => ReportSettingResource::getUrl('edit-for-mode', ['mode' => $record])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

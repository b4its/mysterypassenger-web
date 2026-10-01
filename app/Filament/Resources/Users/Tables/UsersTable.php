<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable()->weight('bold')
                    ->description(fn ($record) => $record->username),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('role')->label('Peran')->badge()->sortable(),
                TextColumn::make('organization')->label('Instansi')->toggleable()->placeholder('—'),
                ToggleColumn::make('is_active')->label('Aktif'),
                TextColumn::make('surveys_count')->counts('surveys')->label('Survei')->badge()->color('info'),
                TextColumn::make('last_login_at')->label('Login Terakhir')->since()->placeholder('Belum pernah')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')->options(UserRole::class)->multiple(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

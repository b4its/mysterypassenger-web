<?php

namespace App\Filament\Resources\FormTemplates\RelationManagers;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignedUsers';

    protected static ?string $title = 'Penugasan Surveyor';

    protected static ?string $modelLabel = 'Surveyor';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Surveyor')
                ->options(fn () => User::where('role', UserRole::Surveyor)->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            DateTimePicker::make('starts_at')->label('Mulai')->seconds(false),
            DateTimePicker::make('due_at')->label('Tenggat')->seconds(false),

            Textarea::make('notes')->label('Catatan')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Surveyor')->searchable()->weight('bold'),
                TextColumn::make('email')->label('Email')->toggleable(),
                TextColumn::make('pivot.due_at')->label('Tenggat')->dateTime('d M Y')->placeholder('—'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Tugaskan Surveyor')
                    ->recordSelectOptionsQuery(fn ($query) => $query->where('role', UserRole::Surveyor))
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DetachBulkAction::make()]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}

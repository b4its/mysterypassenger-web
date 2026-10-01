<?php

namespace App\Filament\Resources\FormTemplates\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Bagian Profil Perjalanan';

    protected static ?string $modelLabel = 'Bagian';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Bagian')
                ->placeholder('Informasi Kapal & Pemilik, Rute & Lokasi')
                ->required()
                ->maxLength(160)
                ->columnSpanFull(),

            TextInput::make('columns')
                ->label('Jumlah Kolom')
                ->numeric()
                ->default(2)
                ->minValue(1)
                ->maxValue(4),

            TextInput::make('sort_order')
                ->label('Urutan')
                ->numeric()
                ->default(0),

            Textarea::make('description')
                ->label('Keterangan')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Bagian')->searchable()->weight('bold'),
                TextColumn::make('columns')->label('Kolom')->badge()->color('gray'),
                TextColumn::make('fields_count')->counts('fields')->label('Field')->badge(),
            ])
            ->headerActions([CreateAction::make()->label('Tambah Bagian')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public function isReadOnly(): bool
    {
        return ! $this->getOwnerRecord()->isEditable();
    }
}

<?php

namespace App\Filament\Resources\FormTemplates\RelationManagers;

use App\Enums\FieldType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';

    protected static ?string $title = 'Field Profil Perjalanan';

    protected static ?string $modelLabel = 'Field';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('label')
                        ->label('Label')
                        ->placeholder('Nama Kapal / Nomor Bus / Nomor KA')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('key', Str::snake((string) $state))),

                    TextInput::make('key')
                        ->label('Key')
                        ->required()
                        ->maxLength(64)
                        ->rules(['regex:/^[a-z][a-z0-9_]*$/'])
                        ->helperText('Huruf kecil, angka, underscore. Dipakai di PDF & API.'),

                    Select::make('field_type')
                        ->label('Tipe Field')
                        ->options(FieldType::class)
                        ->default(FieldType::Text)
                        ->required()
                        ->live()
                        ->native(false),

                    Select::make('template_section_id')
                        ->label('Bagian')
                        ->relationship('section', 'name')
                        ->searchable()
                        ->preload(),

                    TextInput::make('placeholder')->maxLength(160),
                    TextInput::make('helper_text')->label('Teks Bantuan')->maxLength(200),

                    Repeater::make('options')
                        ->label('Opsi Pilihan')
                        ->visible(fn (callable $get) => in_array(
                            $get('field_type'),
                            [FieldType::Select->value, FieldType::Radio->value, FieldType::Checkbox->value],
                            true,
                        ))
                        ->columnSpanFull()
                        ->table([
                            Repeater\TableColumn::make('Nilai'),
                            Repeater\TableColumn::make('Label'),
                        ])
                        ->schema([
                            TextInput::make('value')->required(),
                            TextInput::make('label')->required(),
                        ])
                        ->addActionLabel('Tambah Opsi')
                        ->defaultItems(2),

                    KeyValue::make('validation_rules')
                        ->label('Aturan Validasi Tambahan')
                        ->keyLabel('Rule')
                        ->valueLabel('Parameter')
                        ->helperText('Contoh: max → 255, regex → /^[A-Z]{2}\\d+$/')
                        ->columnSpanFull(),
                ]),

            Section::make('Perilaku')
                ->columns(4)
                ->schema([
                    Toggle::make('is_required')->label('Wajib'),
                    Toggle::make('show_in_table')->label('Kolom Tabel'),
                    Toggle::make('is_filterable')->label('Bisa Difilter'),
                    Toggle::make('show_in_pdf')->label('Tampil di PDF')->default(true),
                    TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('label')->label('Label')->searchable()->weight('bold'),
                TextColumn::make('key')->label('Key')->badge()->color('gray')->copyable(),
                TextColumn::make('field_type')->label('Tipe')->badge(),
                TextColumn::make('section.name')->label('Bagian')->placeholder('—'),
                IconColumn::make('is_required')->label('Wajib')->boolean(),
                IconColumn::make('show_in_pdf')->label('PDF')->boolean(),
            ])
            ->headerActions([CreateAction::make()->label('Tambah Field')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public function isReadOnly(): bool
    {
        return ! $this->getOwnerRecord()->isEditable();
    }
}

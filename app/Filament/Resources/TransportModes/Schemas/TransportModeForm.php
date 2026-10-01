<?php

namespace App\Filament\Resources\TransportModes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TransportModeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Moda')
                ->description('Jenis transportasi bersifat bebas. Tambahkan sebanyak yang dibutuhkan.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Moda')
                        ->placeholder('Kapal Penumpang, Bus AKAP, Kereta Api, …')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state, callable $set) => $set('slug', Str::slug((string) $state))),

                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(140)
                        ->helperText('Dipakai di URL. Dibuat otomatis dari nama.'),

                    TextInput::make('code')
                        ->label('Kode Dokumen')
                        ->placeholder('SHIP, BUS, TRAIN')
                        ->maxLength(20)
                        ->helperText('Prefiks nomor survei, mis. SHIP/2026/03/0001.'),

                    TextInput::make('sort_order')
                        ->label('Urutan Tampil')
                        ->numeric()
                        ->default(0),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Tampilan')
                ->columns(3)
                ->schema([
                    Select::make('icon')
                        ->label('Ikon')
                        ->options(self::iconOptions())
                        ->searchable()
                        ->default('heroicon-o-truck')
                        ->native(false),

                    Select::make('color')
                        ->label('Warna')
                        ->options([
                            'primary' => 'Primary', 'info' => 'Info', 'success' => 'Success',
                            'warning' => 'Warning', 'danger' => 'Danger', 'gray' => 'Gray',
                        ])
                        ->default('primary')
                        ->native(false),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Moda non-aktif tidak bisa dipilih saat membuat survei baru.'),
                ]),
        ]);
    }

    /** @return array<string,string> */
    public static function iconOptions(): array
    {
        return [
            'heroicon-o-lifebuoy' => 'Kapal / Pelayaran',
            'heroicon-o-truck' => 'Bus / Darat',
            'heroicon-o-map' => 'Kereta',
            'heroicon-o-paper-airplane' => 'Pesawat',
            'heroicon-o-building-office-2' => 'Terminal / Stasiun',
            'heroicon-o-globe-alt' => 'Umum',
        ];
    }
}

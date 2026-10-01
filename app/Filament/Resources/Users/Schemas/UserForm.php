<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Nama Lengkap')->required()->maxLength(160),

                    TextInput::make('username')->label('Username')->maxLength(60)
                        ->unique(ignoreRecord: true)
                        ->helperText('Opsional. Untuk login cepat.'),

                    TextInput::make('email')->label('Email')->email()->required()
                        ->unique(ignoreRecord: true)->maxLength(180),

                    Select::make('role')
                        ->label('Peran')
                        ->options(UserRole::class)
                        ->default(UserRole::Surveyor)
                        ->required()
                        ->native(false),

                    TextInput::make('phone')->label('Telepon')->tel()->maxLength(30),
                    TextInput::make('organization')->label('Instansi')->maxLength(160),
                ]),

            Section::make('Akun')
                ->columns(2)
                ->schema([
                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn ($state) => filled($state))
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->helperText('Biarkan kosong untuk mempertahankan password lama.'),

                    Toggle::make('is_active')
                        ->label('Akun Aktif')
                        ->default(true)
                        ->helperText('Akun non-aktif tidak dapat login ke panel.'),
                ]),
        ]);
    }
}

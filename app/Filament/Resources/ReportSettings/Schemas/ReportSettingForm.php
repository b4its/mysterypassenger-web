<?php

namespace App\Filament\Resources\ReportSettings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kop Surat')
                ->columns(2)
                ->schema([
                    TextInput::make('organization_name')
                        ->label('Nama Instansi')
                        ->required()
                        ->maxLength(180)
                        ->columnSpanFull(),

                    FileUpload::make('logo_path')
                        ->label('Logo')
                        ->disk('report_assets')
                        ->directory('logos')
                        ->image()
                        ->maxSize(2048),

                    Repeater::make('letterhead_lines')
                        ->label('Baris Alamat / Keterangan')
                        ->columnSpanFull()
                        ->addActionLabel('Tambah Baris')
                        ->schema([
                            TextInput::make('text')->label('Teks')->required(),
                        ]),
                ]),

            Section::make('Judul Dokumen')
                ->columns(2)
                ->schema([
                    TextInput::make('checklist_title')
                        ->label('Judul Lembar Ceklist')
                        ->required()
                        ->maxLength(200),

                    TextInput::make('report_title')
                        ->label('Judul Laporan Kegiatan')
                        ->required()
                        ->maxLength(200),

                    Textarea::make('footer_note')
                        ->label('Catatan Kaki')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Halaman')
                ->columns(4)
                ->schema([
                    Select::make('paper_size')
                        ->label('Ukuran Kertas')
                        ->options(['a4' => 'A4', 'legal' => 'Legal', 'letter' => 'Letter'])
                        ->default('a4')
                        ->native(false),

                    Select::make('orientation')
                        ->label('Orientasi')
                        ->options(['landscape' => 'Landscape', 'portrait' => 'Portrait'])
                        ->default('landscape')
                        ->native(false),

                    TextInput::make('photos_per_row')
                        ->label('Foto per Baris')
                        ->numeric()->default(2)->minValue(1)->maxValue(4),

                    Toggle::make('show_photos')->label('Tampilkan Foto')->default(true),
                    Toggle::make('show_scores')->label('Tampilkan Skor')->default(true),
                ]),

            Section::make('Blok Tanda Tangan')
                ->schema([
                    Repeater::make('signature_blocks')
                        ->hiddenLabel()
                        ->addActionLabel('Tambah Blok Tanda Tangan')
                        ->schema([
                            TextInput::make('label')->label('Jabatan Blok')->required(),
                            TextInput::make('name')->label('Nama (opsional)'),
                            TextInput::make('position')->label('Keterangan'),
                        ])
                        ->columns(3),
                ]),
        ]);
    }
}

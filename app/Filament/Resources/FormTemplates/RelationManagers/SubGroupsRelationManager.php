<?php

namespace App\Filament\Resources\FormTemplates\RelationManagers;

use App\Enums\OutputSection;
use App\Models\QuestionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mengelola seluruh indikator termasuk sub-indikator (level 2–3).
 *
 * Repeater bersarang tiga tingkat dihindari (performa Livewire), sehingga
 * hierarki dikelola di sini: admin memilih "Induk" untuk menjadikan sebuah
 * kelompok sebagai sub-indikator. Kedalaman dihitung otomatis oleh model.
 */
class SubGroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'questionGroups';

    protected static ?string $title = 'Sub-Indikator (level 2–3)';

    protected static ?string $modelLabel = 'Indikator';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Indikator')
                ->required()
                ->maxLength(160)
                ->columnSpanFull(),

            Select::make('parent_id')
                ->label('Induk (Indikator Utama)')
                ->options(fn (?QuestionGroup $record) => $this->parentOptions($record))
                ->placeholder('— Tidak ada (indikator utama / level 1) —')
                ->searchable()
                ->native(false)
                ->helperText('Biarkan kosong untuk indikator utama. Pilih induk untuk menjadikannya sub-indikator.'),

            Select::make('output_section')
                ->label('Masuk Dokumen')
                ->options(OutputSection::class)
                ->default(OutputSection::Checklist)
                ->required()
                ->native(false),

            TextInput::make('weight')
                ->label('Bobot')
                ->numeric()->default(1)->step(0.01)->minValue(0),

            TextInput::make('sort_order')
                ->label('Urutan')
                ->numeric()->default(0),

            Toggle::make('is_active')->label('Aktif')->default(true),

            Textarea::make('description')
                ->label('Keterangan')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['parent', 'questions']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Indikator')
                    ->formatStateUsing(fn (QuestionGroup $record) => str_repeat('— ', $record->depth).$record->name)
                    ->searchable()
                    ->weight(fn (QuestionGroup $record) => $record->depth === 0 ? 'bold' : null),
                TextColumn::make('parent.name')->label('Induk')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('depth')->label('Level')->badge()->color('info')
                    ->formatStateUsing(fn (int $state) => 'L'.($state + 1)),
                TextColumn::make('output_section')->label('Dokumen')->badge(),
                TextColumn::make('questions_count')->counts('questions')->label('Pertanyaan')->badge(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah Indikator/Sub'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (QuestionGroup $record) => $record->questions()->doesntExist()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return ! $this->getOwnerRecord()->isEditable();
    }

    /**
     * Opsi induk: seluruh indikator template ini kecuali dirinya dan turunannya
     * (mencegah siklus). Kedalaman dibatasi 3 level (depth 0..2).
     *
     * @return array<int, string>
     */
    private function parentOptions(?QuestionGroup $record): array
    {
        $excludeIds = $record ? $this->descendantIds($record) + [$record->id => true] : [];

        return QuestionGroup::query()
            ->where('form_template_id', $this->getOwnerRecord()->id)
            ->where('depth', '<', 2)                 // maksimal 3 level (0,1,2)
            ->when($excludeIds, fn (Builder $q) => $q->whereNotIn('id', array_keys($excludeIds)))
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (QuestionGroup $g) => [$g->id => str_repeat('— ', $g->depth).$g->name])
            ->all();
    }

    /** @return array<int, bool> */
    private function descendantIds(QuestionGroup $group): array
    {
        $ids = [];

        foreach ($group->children as $child) {
            $ids[$child->id] = true;

            foreach ($this->descendantIds($child) as $id => $v) {
                $ids[$id] = true;
            }
        }

        return $ids;
    }
}

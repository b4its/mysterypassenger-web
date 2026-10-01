<?php

namespace App\Filament\Resources\FormTemplates;

use App\Filament\Resources\FormTemplates\Pages\BuildFormTemplate;
use App\Filament\Resources\FormTemplates\Pages\CreateFormTemplate;
use App\Filament\Resources\FormTemplates\Pages\EditFormTemplate;
use App\Filament\Resources\FormTemplates\Pages\ListFormTemplates;
use App\Filament\Resources\FormTemplates\RelationManagers\AssignmentsRelationManager;
use App\Filament\Resources\FormTemplates\RelationManagers\FieldsRelationManager;
use App\Filament\Resources\FormTemplates\RelationManagers\SectionsRelationManager;
use App\Filament\Resources\FormTemplates\RelationManagers\SubGroupsRelationManager;
use App\Filament\Resources\FormTemplates\Schemas\FormTemplateForm;
use App\Filament\Resources\FormTemplates\Tables\FormTemplatesTable;
use App\Models\FormTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FormTemplateResource extends Resource
{
    protected static ?string $model = FormTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|\UnitEnum|null $navigationGroup = 'Konfigurasi Formulir';

    protected static ?string $navigationLabel = 'Template Formulir';

    protected static ?string $modelLabel = 'Template Formulir';

    protected static ?string $pluralModelLabel = 'Template Formulir';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FormTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
            FieldsRelationManager::class,
            SubGroupsRelationManager::class,
            AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormTemplates::route('/'),
            'create' => CreateFormTemplate::route('/create'),
            'edit' => EditFormTemplate::route('/{record}/edit'),
            'build' => BuildFormTemplate::route('/{record}/build'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}

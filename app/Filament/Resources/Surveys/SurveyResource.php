<?php

namespace App\Filament\Resources\Surveys;

use App\Enums\SurveyStatus;
use App\Filament\Resources\Surveys\Pages\CreateSurvey;
use App\Filament\Resources\Surveys\Pages\FillSurvey;
use App\Filament\Resources\Surveys\Pages\ListSurveys;
use App\Filament\Resources\Surveys\Pages\ViewSurvey;
use App\Filament\Resources\Surveys\Schemas\SurveyForm;
use App\Filament\Resources\Surveys\Schemas\SurveyInfolist;
use App\Filament\Resources\Surveys\Tables\SurveysTable;
use App\Models\Survey;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Survei';

    protected static ?string $navigationLabel = 'Survei';

    protected static ?string $modelLabel = 'Survei';

    protected static ?string $pluralModelLabel = 'Survei';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return SurveyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SurveyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SurveysTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['transportMode', 'formTemplate', 'surveyor', 'fieldValues'])
            ->visibleTo(auth()->user());
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'evaluator_name'];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user?->isReviewer() && ! $user?->isAdmin()) {
            return null;
        }

        $count = Survey::where('status', SurveyStatus::Submitted)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveys::route('/'),
            'create' => CreateSurvey::route('/create'),
            'fill' => FillSurvey::route('/{record}/fill'),
            'view' => ViewSurvey::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Survey;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestSurveysTable extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Survei Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Survey::query()
                    ->visibleTo(auth()->user())
                    ->with(['transportMode'])
                    ->latest('executed_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('code')->label('No. Dokumen')->weight('bold')->searchable(),
                TextColumn::make('transportMode.name')->label('Moda')->badge()
                    ->color(fn (Survey $record) => $record->transportMode?->color),
                TextColumn::make('evaluator_name')->label('Evaluator'),
                TextColumn::make('executed_at')->label('Pelaksanaan')->dateTime('d M Y H:i'),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Survey $record) => SurveyResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}

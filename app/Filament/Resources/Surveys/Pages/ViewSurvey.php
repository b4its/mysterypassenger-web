<?php

namespace App\Filament\Resources\Surveys\Pages;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Survey;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSurvey extends ViewRecord
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fill')
                ->label('Isi / Edit')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (Survey $record) => auth()->user()?->can('update', $record) ?? false)
                ->url(fn (Survey $record) => SurveyResource::getUrl('fill', ['record' => $record])),

            Action::make('pdfChecklist')
                ->label('PDF Ceklist')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (Survey $record) => route('surveys.pdf', ['survey' => $record, 'section' => 'checklist']))
                ->openUrlInNewTab(),

            Action::make('pdfReport')
                ->label('PDF Laporan')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (Survey $record) => route('surveys.pdf', ['survey' => $record, 'section' => 'report']))
                ->openUrlInNewTab(),

            Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->url(fn (Survey $record) => route('surveys.print', ['survey' => $record, 'section' => 'checklist']))
                ->openUrlInNewTab(),
        ];
    }
}

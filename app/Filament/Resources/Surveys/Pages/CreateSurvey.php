<?php

namespace App\Filament\Resources\Surveys\Pages;

use App\Enums\SurveyStatus;
use App\Filament\Resources\FormTemplates\FormTemplateResource;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Filament\Resources\TransportModes\TransportModeResource;
use App\Models\FormTemplate;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateSurvey extends CreateRecord
{
    protected static string $resource = SurveyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $template = FormTemplate::findOrFail($data['form_template_id']);

        return $data + [
            'user_id' => auth()->id(),
            'transport_mode_id' => $template->transport_mode_id,
            'template_version' => $template->version,
            'status' => SurveyStatus::Draft,
        ];
    }

    protected function getRedirectUrl(): string
    {
        return SurveyResource::getUrl('fill', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageTransportModes')
                ->label('Kelola Jenis Kendaraan')
                ->icon('heroicon-o-truck')
                ->color('gray')
                ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                ->url(TransportModeResource::getUrl('index')),

            Action::make('manageTemplates')
                ->label('Kelola Template Formulir')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                ->url(FormTemplateResource::getUrl('index')),
        ];
    }
}

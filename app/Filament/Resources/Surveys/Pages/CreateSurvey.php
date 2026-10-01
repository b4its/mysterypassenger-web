<?php

namespace App\Filament\Resources\Surveys\Pages;

use App\Enums\SurveyStatus;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\FormTemplate;
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
}

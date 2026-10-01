<?php

namespace App\Filament\Resources\FormTemplates\Pages;

use App\Filament\Resources\FormTemplates\FormTemplateResource;
use App\Models\FormTemplate;
use App\Services\FormTemplateVersionService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditFormTemplate extends EditRecord
{
    protected static string $resource = FormTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('build')
                ->label('Susun Pertanyaan')
                ->icon('heroicon-o-queue-list')
                ->url(fn (FormTemplate $record) => FormTemplateResource::getUrl('build', ['record' => $record])),

            Action::make('newVersion')
                ->label('Versi Baru')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (FormTemplateVersionService $service) {
                    $new = $service->createNewVersion($this->record);

                    Notification::make()
                        ->title("Versi {$new->version} dibuat sebagai draf")
                        ->success()
                        ->send();

                    return redirect(FormTemplateResource::getUrl('build', ['record' => $new]));
                }),

            DeleteAction::make()
                ->visible(fn (FormTemplate $record) => $record->surveys()->doesntExist()),
        ];
    }
}

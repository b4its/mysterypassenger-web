<?php

namespace App\Filament\Resources\Surveys\Pages;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Filament\Schemas\SurveyFormSchemaBuilder;
use App\Services\SurveySubmissionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;

class FillSurvey extends Page implements HasSchemas
{
    use InteractsWithRecord;
    use InteractsWithSchemas;

    protected static string $resource = SurveyResource::class;

    protected string $view = 'filament.pages.fill-survey';

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
        abort_unless(auth()->user()->can('update', $this->record), 403);

        $this->form->fill(
            app(SurveyFormSchemaBuilder::class)->hydrate($this->record),
        );
    }

    public function getTitle(): string
    {
        return "Isi Survei — {$this->record->code}";
    }

    public function getSubheading(): ?string
    {
        return "{$this->record->transportMode->name} · {$this->record->formTemplate->name} v{$this->record->template_version}";
    }

    public function form(Schema $schema): Schema
    {
        return app(SurveyFormSchemaBuilder::class)->build($schema, $this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveDraft')
                ->label('Simpan Draf')
                ->icon('heroicon-o-inbox-arrow-down')
                ->color('gray')
                ->action(fn (SurveySubmissionService $service) => $this->persist($service, submit: false)),

            Action::make('submit')
                ->label('Kirim Survei')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Kirim Survei')
                ->modalDescription('Setelah dikirim, survei tidak dapat diubah sampai reviewer mengembalikannya. Lanjutkan?')
                ->action(fn (SurveySubmissionService $service) => $this->persist($service, submit: true)),
        ];
    }

    private function persist(SurveySubmissionService $service, bool $submit): void
    {
        $state = $this->form->getState();          // menjalankan validasi penuh

        $service->save($this->record, $state, submit: $submit);

        Notification::make()
            ->title($submit ? 'Survei terkirim' : 'Draf tersimpan')
            ->success()
            ->send();

        if ($submit) {
            $this->redirect(SurveyResource::getUrl('view', ['record' => $this->record]));
        }
    }

    /** Autosave ringan untuk draf (dipicu wire:poll). */
    public function autosave(): void
    {
        if ($this->record->status->isLocked()) {
            return;
        }

        app(SurveySubmissionService::class)->save(
            $this->record,
            $this->form->getRawState(),
            submit: false,
        );
    }
}

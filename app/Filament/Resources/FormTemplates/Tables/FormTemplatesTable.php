<?php

namespace App\Filament\Resources\FormTemplates\Tables;

use App\Enums\TemplateStatus;
use App\Filament\Resources\FormTemplates\FormTemplateResource;
use App\Models\FormTemplate;
use App\Services\FormTemplateVersionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FormTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('transportMode.name')->label('Moda')->badge()
                    ->color(fn (FormTemplate $record) => $record->transportMode?->color)->sortable(),
                TextColumn::make('name')->label('Template')->searchable()->sortable()->weight('bold'),
                TextColumn::make('version')->label('Ver.')->badge()->color('gray'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('question_groups_count')->counts('questionGroups')->label('Indikator'),
                TextColumn::make('questions_count')->counts('questions')->label('Pertanyaan'),
                TextColumn::make('surveys_count')->counts('surveys')->label('Dipakai')->color('info'),
                TextColumn::make('published_at')->label('Diterbitkan')->dateTime('d M Y H:i')->since()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('transport_mode_id')->relationship('transportMode', 'name')->label('Moda')->multiple(),
                SelectFilter::make('status')->options(TemplateStatus::class),
            ])
            ->recordActions([
                Action::make('build')
                    ->label('Susun Pertanyaan')
                    ->icon('heroicon-o-queue-list')
                    ->color('primary')
                    ->url(fn (FormTemplate $record) => FormTemplateResource::getUrl('build', ['record' => $record])),

                EditAction::make(),

                Action::make('publish')
                    ->label('Terbitkan')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (FormTemplate $record) => $record->status === TemplateStatus::Draft)
                    ->requiresConfirmation()
                    ->modalHeading('Terbitkan Template')
                    ->modalDescription('Setelah diterbitkan, struktur template tidak dapat diubah. Gunakan "Versi Baru" untuk revisi.')
                    ->action(fn (FormTemplate $record) => $record->update([
                        'status' => TemplateStatus::Published,
                        'published_at' => now(),
                    ])),

                Action::make('newVersion')
                    ->label('Versi Baru')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->visible(fn (FormTemplate $record) => $record->status === TemplateStatus::Published)
                    ->requiresConfirmation()
                    ->action(function (FormTemplate $record, FormTemplateVersionService $service) {
                        $new = $service->createNewVersion($record);

                        Notification::make()
                            ->title("Versi {$new->version} dibuat sebagai draf")
                            ->success()
                            ->send();

                        return redirect(FormTemplateResource::getUrl('build', ['record' => $new]));
                    }),

                DeleteAction::make()->visible(fn (FormTemplate $record) => $record->surveys()->doesntExist()),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

<?php

namespace App\Filament\Resources\Surveys\Schemas;

use App\Enums\AnswerType;
use App\Enums\OutputSection;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use Filament\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SurveyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Survei')
                ->columns(3)
                ->schema([
                    TextEntry::make('code')->label('No. Dokumen')->copyable()->weight('bold'),
                    TextEntry::make('transportMode.name')->label('Moda')->badge(),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('evaluator_name')->label('Evaluator'),
                    TextEntry::make('surveyor.name')->label('Surveyor'),
                    TextEntry::make('executed_at')->label('Pelaksanaan')->dateTime('l, d F Y H:i'),
                ]),

            Section::make('Profil Perjalanan')
                ->schema([
                    RepeatableEntry::make('fieldValues')
                        ->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('field_label')->hiddenLabel()->weight('bold')->size('xs')->color('gray'),
                            TextEntry::make('value_text')->hiddenLabel()->placeholder('—')->columnSpan(2),
                        ]),
                ]),

            Section::make('Lokasi')
                ->columns(2)
                ->schema([
                    TextEntry::make('location_text')->label('Lokasi')->placeholder('—')
                        ->hintAction(
                            Action::make('openMap')
                                ->label('Buka Maps')
                                ->icon('heroicon-m-map-pin')
                                ->color('info')
                                ->visible(fn (Survey $r) => $r->latitude && $r->longitude)
                                ->url(fn (Survey $r) => "https://www.google.com/maps/search/?api=1&query={$r->latitude},{$r->longitude}", shouldOpenInNewTab: true),
                        ),
                    TextEntry::make('coordinates')
                        ->label('Koordinat')
                        ->state(fn (Survey $r) => $r->latitude ? "{$r->latitude}, {$r->longitude}" : '—'),
                ]),

            Section::make('Hasil Penilaian')
                ->columns(4)
                ->visible(fn (Survey $r) => (bool) $r->formTemplate?->scoring_enabled)
                ->schema([
                    TextEntry::make('total_score')->label('Skor'),
                    TextEntry::make('max_score')->label('Skor Maksimum'),
                    TextEntry::make('score_percentage')->label('Persentase')->suffix('%'),
                    IconEntry::make('is_passed')->label('Lulus')->boolean(),
                ]),

            Tabs::make('Jawaban')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Ceklist')->schema(self::answerEntries(OutputSection::Checklist)),
                    Tabs\Tab::make('Laporan')->schema(self::answerEntries(OutputSection::Report)),
                ]),

            Section::make('Review')
                ->columns(3)
                ->visible(fn (Survey $r) => $r->reviewed_at !== null)
                ->schema([
                    TextEntry::make('reviewer.name')->label('Direview oleh'),
                    TextEntry::make('reviewed_at')->label('Waktu Review')->dateTime('d M Y H:i'),
                    TextEntry::make('review_note')->label('Catatan')->columnSpanFull()->markdown(),
                ]),
        ]);
    }

    /**
     * @return array<int, RepeatableEntry>
     */
    public static function answerEntries(OutputSection $section): array
    {
        return [
            RepeatableEntry::make("answers_{$section->value}")
                ->hiddenLabel()
                ->state(fn (Survey $record) => $record->answers()
                    ->with(['question.group', 'media'])
                    ->whereHas('group', fn (Builder $q) => $q->whereIn('output_section', [
                        $section->value,
                        OutputSection::Both->value,
                    ]))
                    ->get())
                ->schema([
                    TextEntry::make('question.group.name')->label('Indikator')->badge()->color('gray'),
                    TextEntry::make('question.text')->label('Pertanyaan')->weight('bold')->columnSpanFull(),
                    TextEntry::make('display')
                        ->label('Jawaban')
                        ->state(fn (SurveyAnswer $r) => $r->displayValue())
                        ->badge(fn (SurveyAnswer $r) => $r->answer_type === AnswerType::Boolean)
                        ->color(fn (SurveyAnswer $r) => match ($r->value_boolean) {
                            true => 'success', false => 'danger', default => 'gray',
                        }),
                    TextEntry::make('note')->label('Catatan')->placeholder('—'),
                    RepeatableEntry::make('media')
                        ->label('Bukti')
                        ->columns(3)
                        ->columnSpanFull()
                        ->schema([
                            ImageEntry::make('path')
                                ->hiddenLabel()
                                ->disk('survey_media')
                                ->visibility('private')
                                ->height(160),
                        ]),
                ])
                ->columns(2),
        ];
    }
}

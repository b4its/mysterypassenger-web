<?php

namespace App\Jobs;

use App\Enums\OutputSection;
use App\Models\Survey;
use App\Models\User;
use App\Services\SurveyPdfRenderer;
use App\Services\SurveyReportComposer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class GenerateSurveyPdfBundle implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 2;

    /** @param  array<int,int>  $surveyIds */
    public function __construct(
        public array $surveyIds,
        public int $userId,
        public string $section = 'checklist',
    ) {}

    public function handle(SurveyReportComposer $composer, SurveyPdfRenderer $renderer): void
    {
        $section = OutputSection::tryFrom($this->section) ?? OutputSection::Checklist;
        $user = User::findOrFail($this->userId);

        $relative = 'exports/pdf-bundle-'.now()->format('Ymd-His').'-'.Str::random(6).'.zip';
        $absolute = Storage::disk('local')->path($relative);

        Storage::disk('local')->makeDirectory('exports');

        $zip = new ZipArchive;

        if ($zip->open($absolute, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat arsip ZIP.');
        }

        $processed = 0;

        Survey::query()
            ->whereIn('id', $this->surveyIds)
            ->with(['transportMode', 'formTemplate', 'fieldValues', 'answers.question.group', 'answers.media'])
            ->chunkById(25, function ($surveys) use ($zip, $composer, $renderer, $section, $user, &$processed) {
                foreach ($surveys as $survey) {
                    if ($user->cannot('print', $survey)) {
                        continue;                       // hormati otorisasi per record
                    }

                    $pdf = $renderer->render($composer->compose($survey, $section));
                    $name = str($survey->code)->replace('/', '-')->append('-', $section->value, '.pdf')->toString();

                    $zip->addFromString($name, $pdf->output());

                    unset($pdf);
                    gc_collect_cycles();                // tahan penggunaan memori
                    $processed++;
                }
            });

        $zip->close();

        // ZipArchive tidak menulis berkas bila tidak ada entri (mis. seluruh
        // survei ditolak policy). Hapus sisa berkas dan beri tahu pengguna.
        if ($processed === 0 || ! is_file($absolute)) {
            if (is_file($absolute)) {
                @unlink($absolute);
            }

            Notification::make()
                ->title('Tidak ada survei yang dapat diproses')
                ->body('Tidak ada survei yang memenuhi izin Anda untuk dibuatkan PDF.')
                ->warning()
                ->sendToDatabase($user);

            return;
        }

        Notification::make()
            ->title('Paket PDF siap diunduh')
            ->body("{$processed} survei telah diproses.")
            ->success()
            ->actions([
                Action::make('download')
                    ->label('Unduh ZIP')
                    ->url(route('exports.download', ['path' => encrypt($relative)]), shouldOpenInNewTab: true)
                    ->markAsRead(),
            ])
            ->sendToDatabase($user);
    }
}

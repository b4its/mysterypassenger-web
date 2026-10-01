<?php

use App\Jobs\GenerateSurveyPdfBundle;
use App\Jobs\PruneExpiredExportFiles;
use App\Models\FormTemplate;
use App\Models\Survey;
use App\Services\SurveyPdfRenderer;
use App\Services\SurveyReportComposer;
use Illuminate\Support\Facades\Storage;

it('menghasilkan arsip ZIP berisi PDF untuk setiap survei', function () {
    Storage::fake('local');
    Storage::fake('survey_media');

    $admin = actingAsAdmin();
    $surveys = Survey::factory()->count(3)->submitted()->create();

    (new GenerateSurveyPdfBundle($surveys->pluck('id')->all(), $admin->id))
        ->handle(
            app(SurveyReportComposer::class),
            app(SurveyPdfRenderer::class),
        );

    $files = Storage::disk('local')->files('exports');

    expect($files)->toHaveCount(1)
        ->and($files[0])->toEndWith('.zip');

    $zipPath = Storage::disk('local')->path($files[0]);
    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect($zip->numFiles)->toBe(3);
    $zip->close();
});

it('menghormati otorisasi policy saat membuat ZIP', function () {
    Storage::fake('local');
    Storage::fake('survey_media');

    $surveyor = actingAsSurveyor();

    // Survei milik orang lain tidak boleh ikut terpaket.
    Survey::factory()->create(['user_id' => $surveyor->id]);
    $others = Survey::factory()->create();

    (new GenerateSurveyPdfBundle([$others->id], $surveyor->id))
        ->handle(
            app(SurveyReportComposer::class),
            app(SurveyPdfRenderer::class),
        );

    // Semua milik orang lain → tak ada arsip yang dibuat.
    expect(Storage::disk('local')->files('exports'))->toBeEmpty();
});

it('membersihkan berkas export yang kedaluwarsa', function () {
    Storage::fake('local');

    Storage::disk('local')->put('exports/lama.zip', 'x');
    Storage::disk('local')->put('exports/baru.zip', 'y');

    // Buat file lama (8 hari) dengan mengubah mtime.
    touch(Storage::disk('local')->path('exports/lama.zip'), now()->subDays(8)->getTimestamp());

    (new PruneExpiredExportFiles)->handle();

    Storage::disk('local')->assertMissing('exports/lama.zip');
    Storage::disk('local')->assertExists('exports/baru.zip');
});

it('menghasilkan halaman print yang dapat dirender', function () {
    actingAsAdmin();

    $template = FormTemplate::factory()->published()->withFields()->withChecklist(1, 2)->create();
    $survey = Survey::factory()->for($template)->submitted()->withAnswers(1, 2)->create();

    $this->get(route('surveys.print', ['survey' => $survey, 'section' => 'checklist', 'auto' => 0]))
        ->assertOk()
        ->assertSee('LEMBAR CEKLIST');

    $this->get(route('surveys.print', ['survey' => $survey, 'section' => 'report', 'auto' => 0]))
        ->assertOk();
});

it('tidak menampilkan tombol cetak otomatis ketika auto=0', function () {
    actingAsAdmin();

    $survey = Survey::factory()->submitted()->create();

    $response = $this->get(route('surveys.print', ['survey' => $survey, 'auto' => 0]));

    $response->assertOk();
    expect($response->getContent())->not->toContain('window.addEventListener(\'load\'');
});

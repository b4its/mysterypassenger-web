<?php

use App\Enums\AnswerType;
use App\Enums\OutputSection;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\ReportSetting;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAnswerMedia;
use App\Models\SurveyFieldValue;
use App\Services\SurveyReportComposer;
use App\Services\SurveySnapshotService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

it('menghasilkan PDF ceklist yang valid', function () {
    actingAsAdmin();

    $survey = Survey::factory()->submitted()->create();

    $response = $this->get(route('surveys.pdf', ['survey' => $survey, 'section' => 'checklist']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->getContent())->toStartWith('%PDF-');
});

it('memakai judul dari report_settings, bukan nilai hardcode', function () {
    actingAsAdmin();

    $survey = Survey::factory()->submitted()->create();

    ReportSetting::factory()->create([
        'transport_mode_id' => $survey->transport_mode_id,
        'checklist_title' => 'LEMBAR PEMERIKSAAN ARMADA',
    ]);

    $data = app(SurveyReportComposer::class)->compose($survey, OutputSection::Checklist);

    expect($data['title'])->toBe('LEMBAR PEMERIKSAAN ARMADA');
});

it('memakai kop instansi dari report_settings (bukan hardcode)', function () {
    actingAsAdmin();

    $survey = Survey::factory()->submitted()->create();

    ReportSetting::factory()->create([
        'transport_mode_id' => $survey->transport_mode_id,
        'organization_name' => 'DINAS PERHUBUNGAN KHUSUS',
    ]);

    $data = app(SurveyReportComposer::class)->compose($survey->refresh(), OutputSection::Checklist);

    expect($data['setting']->organization_name)->toBe('DINAS PERHUBUNGAN KHUSUS');

    $html = view('pdf.survey-checklist', $data)->render();
    expect($html)->toContain('DINAS PERHUBUNGAN KHUSUS');
});

it('memakai label field dari template pada baris kop', function () {
    actingAsAdmin();

    $template = FormTemplate::factory()->published()
        ->withFields(['nama_kapal' => 'Nama Kapal'])
        ->withChecklist(1, 1)
        ->create();

    $survey = Survey::factory()->for($template)->submitted()->create();

    SurveyFieldValue::factory()->create([
        'survey_id' => $survey->id,
        'field_key' => 'nama_kapal',
        'field_label' => 'Nama Kapal',
        'value' => 'KM Test',
        'value_text' => 'KM Test',
    ]);

    $data = app(SurveyReportComposer::class)->compose($survey->refresh(), OutputSection::Checklist);

    $labels = collect($data['metaRows'])->pluck('label');
    expect($labels)->toContain('Nama Kapal');
});

it('menghitung rowspan sebesar jumlah pertanyaan dalam indikator', function () {
    actingAsAdmin();

    $template = FormTemplate::factory()->published()->withChecklist(2, 4)->create();
    $survey = Survey::factory()->for($template)->submitted()->withAnswers(2, 4)->create();

    $data = app(SurveyReportComposer::class)->compose($survey->refresh(), OutputSection::Checklist);

    expect($data['groups'])->toHaveCount(2)
        ->and($data['groups']->first()['rowspan'])->toBe(4);
});

it('memformat tanggal pelaksanaan dalam Bahasa Indonesia', function () {
    actingAsAdmin();

    $survey = Survey::factory()->submitted()->create([
        'executed_at' => Carbon::parse('2026-10-02 08:00:00'),
    ]);

    $data = app(SurveyReportComposer::class)->compose($survey->refresh(), OutputSection::Checklist);

    $tanggal = collect($data['metaRows'])->firstWhere('label', 'Tanggal Pelaksanaan');

    expect($tanggal)->not->toBeNull()
        ->and($tanggal['value'])->toBe('Jumat, 02 - Oktober - 2026');
});

it('memisahkan dokumen ceklist dan laporan berdasarkan output_section', function () {
    actingAsAdmin();

    $template = FormTemplate::factory()->published()->create();
    $checkGroup = QuestionGroup::factory()->for($template)->create([
        'output_section' => OutputSection::Checklist, 'sort_order' => 1,
    ]);
    $reportGroup = QuestionGroup::factory()->for($template)->create([
        'output_section' => OutputSection::Report, 'sort_order' => 2,
    ]);

    $q1 = Question::factory()->for($template)->for($checkGroup, 'group')->create();
    $q2 = Question::factory()->for($template)->for($reportGroup, 'group')->create([
        'answer_type' => AnswerType::TextLong, 'max_score' => 0,
    ]);

    $survey = Survey::factory()->for($template)->submitted()->create();

    SurveyAnswer::factory()->create([
        'survey_id' => $survey->id, 'question_id' => $q1->id, 'question_group_id' => $checkGroup->id,
        'answer_type' => AnswerType::Boolean, 'value_boolean' => true,
    ]);
    SurveyAnswer::factory()->create([
        'survey_id' => $survey->id, 'question_id' => $q2->id, 'question_group_id' => $reportGroup->id,
        'answer_type' => AnswerType::TextLong, 'value_text' => 'Uraian',
    ]);

    $checklist = app(SurveyReportComposer::class)->compose($survey->refresh(), OutputSection::Checklist);
    $report = app(SurveyReportComposer::class)->compose($survey->refresh(), OutputSection::Report);

    expect($checklist['groups'])->toHaveCount(1)
        ->and($checklist['groups']->first()['name'])->toBe($checkGroup->name)
        ->and($report['groups'])->toHaveCount(1)
        ->and($report['groups']->first()['name'])->toBe($reportGroup->name);
});

it('menanamkan foto sebagai data URI', function () {
    actingAsAdmin();
    Storage::fake('survey_media');

    $template = FormTemplate::factory()->published()->withChecklist(1, 1)->create();
    $survey = Survey::factory()->for($template)->submitted()->create();
    $question = $template->questions()->first();

    $answer = SurveyAnswer::factory()->create([
        'survey_id' => $survey->id,
        'question_id' => $question->id,
        'question_group_id' => $question->question_group_id,
        'answer_type' => AnswerType::Boolean,
        'value_boolean' => true,
    ]);

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    Storage::disk('survey_media')->put('a/b/photo.png', $png);

    SurveyAnswerMedia::factory()->create([
        'survey_answer_id' => $answer->id,
        'disk' => 'survey_media',
        'path' => 'a/b/photo.png',
        'mime_type' => 'image/png',
    ]);

    $data = app(SurveyReportComposer::class)->compose($survey->refresh(), OutputSection::Checklist);

    $photo = collect($data['groups'])->flatMap(fn ($g) => $g['rows'])->flatMap(fn ($r) => $r['photos'])->first();

    expect($photo)->not->toBeNull()
        ->and($photo['src'])->toStartWith('data:image/png;base64,');
});

it('memakai snapshot sehingga PDF lama tidak berubah setelah template direvisi', function () {
    actingAsAdmin();

    $template = FormTemplate::factory()->published()->withFields()->withChecklist(1, 1)->create();
    $survey = Survey::factory()->for($template)->submitted()->withAnswers(1, 1)->create();

    // Simulasikan snapshot yang sudah tertulis saat submit.
    $survey->update(['meta' => app(SurveySnapshotService::class)->build($survey)]);

    $before = app(SurveyReportComposer::class)->compose($survey->refresh(), OutputSection::Checklist);

    $template->questions()->first()->update(['text' => 'TEKS BARU SETELAH REVISI']);

    $after = app(SurveyReportComposer::class)->compose($survey->refresh(), OutputSection::Checklist);

    expect($before['groups'])->not->toBeEmpty()
        ->and($after['groups']->first()['rows'][0]['text'])
        ->toBe($before['groups']->first()['rows'][0]['text'])
        ->and($after['groups']->first()['rows'][0]['text'])
        ->not->toBe('TEKS BARU SETELAH REVISI');
});

it('menolak akses PDF tanpa autentikasi', function () {
    $survey = Survey::factory()->create();

    $this->get(route('surveys.pdf', ['survey' => $survey]))->assertRedirect();
});

it('melarang surveyor mencetak survei orang lain', function () {
    actingAsSurveyor();

    $this->get(route('surveys.pdf', ['survey' => Survey::factory()->create()]))
        ->assertForbidden();
});

it('menyajikan pratinjau PDF secara inline secara default tanpa langsung mengunduh', function () {
    actingAsAdmin();

    $survey = Survey::factory()->submitted()->create();

    $response = $this->get(route('surveys.pdf', ['survey' => $survey]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('inline');
});

it('mengunduh berkas PDF jika parameter download=1 disertakan', function () {
    actingAsAdmin();

    $survey = Survey::factory()->submitted()->create();

    $response = $this->get(route('surveys.pdf', ['survey' => $survey, 'download' => 1]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

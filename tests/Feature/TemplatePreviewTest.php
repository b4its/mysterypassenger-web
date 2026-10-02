<?php

use App\Enums\AnswerType;
use App\Enums\OutputSection;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Survey;
use App\Models\TransportMode;
use App\Services\SurveyReportComposer;

it('menampilkan pratinjau template yang belum pernah dipakai survei (tanpa 404)', function () {
    actingAsAdmin();

    // Template tanpa survei — sebelumnya selalu 404.
    $template = FormTemplate::factory()->published()->withChecklist(1, 2)->create();

    expect(Survey::where('form_template_id', $template->id)->exists())->toBeFalse();

    $this->get(route('templates.preview', ['template' => $template]))
        ->assertOk()
        ->assertSee('LEMBAR CEKLIST')
        ->assertSee('Mode Pratinjau', false);
});

it('menampilkan pratinjau bagian laporan tanpa 404', function () {
    actingAsAdmin();

    $template = FormTemplate::factory()->published()->create();
    $group = QuestionGroup::factory()->for($template)->create([
        'output_section' => OutputSection::Report,
    ]);
    Question::factory()->for($template)->for($group, 'group')->create([
        'answer_type' => AnswerType::TextLong, 'max_score' => 0,
    ]);

    $this->get(route('templates.preview', ['template' => $template, 'section' => 'report']))
        ->assertOk()
        ->assertSee('LAPORAN KEGIATAN');
});

it('membangun data pratinjau dari struktur template (dummy)', function () {
    $template = FormTemplate::factory()->published()
        ->withFields(['nama_kapal' => 'Nama Kapal'])
        ->withChecklist(2, 3)
        ->create();

    $data = app(SurveyReportComposer::class)->composeForTemplate($template, OutputSection::Checklist);

    expect($data['survey']->exists)->toBeFalse()
        ->and($data['groups'])->toHaveCount(2)
        ->and($data['groups']->first()['rows'][0]['isBoolean'])->toBeTrue()
        ->and($data['metaRows'])->not->toBeEmpty();
});

it('menolak pratinjau bagi pengguna tanpa izin (surveyor)', function () {
    actingAsSurveyor();

    $template = FormTemplate::factory()->published()->create();

    $this->get(route('templates.preview', ['template' => $template]))
        ->assertForbidden();
});

it('tidak menghasilkan tautan rute survei yang tidak valid pada mode pratinjau', function () {
    actingAsAdmin();

    $template = FormTemplate::factory()->published()->withChecklist(1, 1)->create();

    // Bila view mencoba route('surveys.print') tanpa id, akan melempar
    // UrlGenerationException (500). Pastikan render sukses.
    $html = view('print.survey', app(SurveyReportComposer::class)
        ->composeForTemplate($template, OutputSection::Checklist) + ['autoPrint' => false])->render();

    expect($html)->toContain('Mode Pratinjau')
        ->and($html)->not->toContain('surveys/print');
});

it('mengizinkan admin melihat pratinjau template dari mode transportasi apa pun', function () {
    actingAsAdmin();

    $mode = TransportMode::factory()->create(['name' => 'Kapal Cepat']);
    $template = FormTemplate::factory()->published()->for($mode)->create();

    $data = app(SurveyReportComposer::class)->composeForTemplate($template, OutputSection::Checklist);

    expect($data['survey']->transport_mode_id)->toBe($mode->id)
        ->and($data['setting'])->not->toBeNull();

    $this->get(route('templates.preview', ['template' => $template]))
        ->assertOk()
        ->assertSee('LEMBAR CEKLIST');
});

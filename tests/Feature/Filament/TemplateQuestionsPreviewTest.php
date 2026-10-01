<?php

use App\Enums\AnswerType;
use App\Enums\OutputSection;
use App\Filament\Resources\Surveys\Pages\CreateSurvey;
use App\Filament\Resources\Surveys\Schemas\SurveyForm;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\QuestionOption;
use App\Models\TemplateField;
use App\Models\TransportMode;
use App\Services\TemplateQuestionPreview;

use function Pest\Livewire\livewire;

beforeEach(fn () => actingAsAdmin());

/* ── Popup ikon (?) ─────────────────────────────────────────────────────── */

it('menampilkan ikon (?) pada select template formulir', function () {
    livewire(CreateSurvey::class)
        ->assertOk()
        ->assertFormComponentActionVisible('form_template_id', 'previewQuestions');
});

it('menampilkan pesan bila belum ada template terpilih', function () {
    // Konten modal dirender setelah mount; verifikasi view-nya secara langsung.
    $html = view('filament.modals.template-questions-empty')->render();

    expect($html)->toContain('Pilih template formulir terlebih dahulu');

    livewire(CreateSurvey::class)
        ->mountFormComponentAction('form_template_id', 'previewQuestions')
        ->assertSuccessful();
});

it('menyusun data pratinjau: field, indikator, dan pertanyaan', function () {
    $mode = TransportMode::factory()->create(['name' => 'Kapal Penumpang']);
    $template = FormTemplate::factory()->published()->for($mode)->create(['name' => 'Template Kapal']);

    TemplateField::factory()->create([
        'form_template_id' => $template->id,
        'key' => 'nama_kapal',
        'label' => 'Nama Kapal',
        'is_required' => true,
    ]);

    $group = QuestionGroup::factory()->for($template)->create([
        'name' => 'Tangibles',
        'output_section' => OutputSection::Checklist,
    ]);

    $question = Question::factory()->for($template)->for($group, 'group')->create([
        'text' => 'Kebersihan ruang penumpang',
        'answer_type' => AnswerType::Boolean,
    ]);

    QuestionOption::factory()->for($question)->create(['label' => 'Ya', 'value' => 'ya', 'score' => 1]);

    $preview = app(TemplateQuestionPreview::class)->build($template);

    expect($preview['name'])->toBe('Template Kapal')
        ->and($preview['transport_mode'])->toBe('Kapal Penumpang')
        ->and($preview['fields'])->toHaveCount(1)
        ->and($preview['fields'][0]['label'])->toBe('Nama Kapal')
        ->and($preview['fields'][0]['required'])->toBeTrue()
        ->and($preview['groups'])->toHaveCount(1)
        ->and($preview['groups'][0]['name'])->toBe('Tangibles')
        ->and($preview['groups'][0]['section'])->toBe('Lembar Ceklist')
        ->and($preview['groups'][0]['questions'][0]['text'])->toBe('Kebersihan ruang penumpang')
        ->and($preview['groups'][0]['questions'][0]['options'][0]['label'])->toBe('Ya');
});

it('menyusun sub-indikator bersarang secara rekursif', function () {
    $template = FormTemplate::factory()->published()->create();
    $root = QuestionGroup::factory()->for($template)->create(['name' => 'Keselamatan']);
    $child = QuestionGroup::factory()->for($template)->create([
        'parent_id' => $root->id,
        'depth' => 1,
        'name' => 'Sub Keselamatan',
    ]);

    Question::factory()->for($template)->for($child, 'group')->create([
        'text' => 'Pertanyaan sub',
        'answer_type' => AnswerType::Boolean,
    ]);

    $preview = app(TemplateQuestionPreview::class)->build($template);

    expect($preview['groups'][0]['name'])->toBe('Keselamatan')
        ->and($preview['groups'][0]['children'][0]['name'])->toBe('Sub Keselamatan')
        ->and($preview['groups'][0]['children'][0]['questions'][0]['text'])->toBe('Pertanyaan sub');
});

it('menghitung total pertanyaan template', function () {
    $template = FormTemplate::factory()->withChecklist(2, 3)->create();

    expect(app(TemplateQuestionPreview::class)->questionCount($template))->toBe(6);
});

it('merender blade pratinjau tanpa error', function () {
    $template = FormTemplate::factory()->published()->withFields(['nama_kapal' => 'Nama Kapal'])->withChecklist(1, 2)->create();

    $html = view('filament.modals.template-questions', [
        'preview' => app(TemplateQuestionPreview::class)->build($template),
    ])->render();

    expect($html)->toContain('Nama Kapal')
        ->toContain('Profil Perjalanan')
        ->toContain('Indikator 1');
});

it('merender pesan kosong bila template tanpa pertanyaan', function () {
    $template = FormTemplate::factory()->published()->create();

    $html = view('filament.modals.template-questions', [
        'preview' => app(TemplateQuestionPreview::class)->build($template),
    ])->render();

    expect($html)->toContain('belum memiliki pertanyaan');
});

/* ── Tambah jenis transportasi inline ───────────────────────────────────── */

it('menyediakan aksi pratinjau dengan nama yang benar', function () {
    expect(SurveyForm::questionPreviewAction()->getName())->toBe('previewQuestions');
});

it('membuat jenis transportasi baru dari select pada wizard', function () {
    livewire(CreateSurvey::class)
        ->callFormComponentAction('transport_mode_id', 'createOption', data: [
            'name' => 'Angkutan Penyeberangan',
            'slug' => 'angkutan-penyeberangan',
            'code' => 'FERRY',
        ])
        ->assertHasNoFormComponentActionErrors();

    expect(TransportMode::where('slug', 'angkutan-penyeberangan')->exists())->toBeTrue();
});

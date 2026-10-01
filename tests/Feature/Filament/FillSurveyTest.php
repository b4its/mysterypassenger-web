<?php

use App\Enums\AnswerType;
use App\Enums\EvidenceRequirement;
use App\Enums\SurveyStatus;
use App\Filament\Resources\Surveys\Pages\FillSurvey;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Survey;
use App\Services\SurveySubmissionService;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

it('merender field profil dinamis sesuai template', function () {
    $surveyor = actingAsSurveyor();

    $template = FormTemplate::factory()->published()
        ->withFields(['nomor_ka' => 'Nomor KA', 'stasiun_asal' => 'Stasiun Asal'])
        ->withChecklist(1, 2)
        ->create();

    $template->assignedUsers()->attach($surveyor);

    $survey = Survey::factory()->for($template)->create([
        'user_id' => $surveyor->id,
        'transport_mode_id' => $template->transport_mode_id,
        'template_version' => $template->version,
    ]);

    livewire(FillSurvey::class, ['record' => $survey->getKey()])
        ->assertOk()
        ->assertFormFieldExists('fields.nomor_ka')
        ->assertFormFieldExists('fields.stasiun_asal')
        ->assertFormFieldDoesNotExist('fields.nama_kapal');
});

it('mengembalikan state utuh ketika halaman dimuat ulang', function () {
    $surveyor = actingAsSurveyor();
    $template = FormTemplate::factory()->published()->withFields()->withChecklist(1, 2)->create();
    $survey = Survey::factory()->for($template)->create([
        'user_id' => $surveyor->id,
        'transport_mode_id' => $template->transport_mode_id,
    ]);

    [$q1, $q2] = $template->questions()->orderBy('sort_order')->get()->all();

    app(SurveySubmissionService::class)->save($survey, [
        'evaluator_name' => 'Budi Santoso',
        'fields' => ['nama_aset' => 'KM Nusantara', 'asal' => 'Tanjung Priok'],
        'answers' => [
            $q1->id => ['value' => true, 'note' => 'Bersih'],
            $q2->id => ['value' => false],
        ],
    ], submit: false);

    livewire(FillSurvey::class, ['record' => $survey->refresh()->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'evaluator_name' => 'Budi Santoso',
            'fields.nama_aset' => 'KM Nusantara',
            'fields.asal' => 'Tanjung Priok',
        ]);
});

it('menyimpan jawaban ke tabel yang benar dan menghitung skor saat submit', function () {
    $surveyor = actingAsSurveyor();
    $template = FormTemplate::factory()->published()->withFields()->withChecklist(1, 2)->create();
    $survey = Survey::factory()->for($template)->create([
        'user_id' => $surveyor->id,
        'transport_mode_id' => $template->transport_mode_id,
    ]);

    [$q1, $q2] = $template->questions()->orderBy('sort_order')->get()->all();

    app(SurveySubmissionService::class)->save($survey, [
        'evaluator_name' => 'Budi Santoso',
        'executed_at' => now()->subHour(),
        'fields' => ['nama_aset' => 'KM Nusantara', 'asal' => 'Tanjung Priok'],
        'answers' => [
            $q1->id => ['value' => true, 'note' => 'Bersih'],
            $q2->id => ['value' => false, 'note' => null],
        ],
    ], submit: true);

    $survey->refresh();

    expect($survey->status)->toBe(SurveyStatus::Submitted)
        ->and($survey->submitted_at)->not->toBeNull()
        ->and($survey->meta)->not->toBeNull()
        ->and($survey->fieldValues)->toHaveCount(2)
        ->and($survey->field('nama_aset'))->toBe('KM Nusantara')
        ->and($survey->answers)->toHaveCount(2)
        ->and($survey->answers->firstWhere('question_id', $q1->id)->value_boolean)->toBeTrue()
        ->and($survey->score_percentage)->toEqual('50.00');
});

it('menyembunyikan pertanyaan yang kondisinya tidak terpenuhi', function () {
    $surveyor = actingAsSurveyor();
    $template = FormTemplate::factory()->published()->withFields()->withChecklist(1, 2)->create();
    [$q1, $q2] = $template->questions()->orderBy('sort_order')->get()->all();

    $q2->update([
        'depends_on_question_id' => $q1->id,
        'depends_on_operator' => 'equals',
        'depends_on_value' => [true],
    ]);

    $survey = Survey::factory()->for($template)->create([
        'user_id' => $surveyor->id,
        'transport_mode_id' => $template->transport_mode_id,
    ]);

    livewire(FillSurvey::class, ['record' => $survey->getKey()])
        ->fillForm(['answers' => [$q1->id => ['value' => false]]])
        ->assertFormFieldHidden("answers.{$q2->id}.value")
        ->fillForm(['answers' => [$q1->id => ['value' => true]]])
        ->assertFormFieldVisible("answers.{$q2->id}.value");
});

it('melarang surveyor membuka survei milik orang lain', function () {
    actingAsSurveyor();
    $survey = Survey::factory()->create();

    // Resource membatasi query → survei orang lain tidak ditemukan (404), bukan 403,
    // agar keberadaannya tidak terungkap.
    livewire(FillSurvey::class, ['record' => $survey->getKey()])
        ->assertNotFound();
});

it('melarang mengedit survei yang sudah disetujui', function () {
    $surveyor = actingAsSurveyor();
    $survey = Survey::factory()->create([
        'user_id' => $surveyor->id,
        'status' => SurveyStatus::Approved,
    ]);

    livewire(FillSurvey::class, ['record' => $survey->getKey()])
        ->assertForbidden();
});

it('menolak submit ketika pertanyaan wajib belum dijawab', function () {
    $surveyor = actingAsSurveyor();
    $template = FormTemplate::factory()->published()->withChecklist(1, 1)->create();
    $survey = Survey::factory()->for($template)->create([
        'user_id' => $surveyor->id,
        'transport_mode_id' => $template->transport_mode_id,
    ]);

    expect(fn () => app(SurveySubmissionService::class)->save($survey, [
        'evaluator_name' => 'Budi',
        'executed_at' => now()->subHour(),
        'answers' => [],
    ], submit: true))->toThrow(ValidationException::class);

    expect($survey->refresh()->status)->toBe(SurveyStatus::Draft);
});

it('menolak submit ketika bukti foto wajib belum diunggah', function () {
    $surveyor = actingAsSurveyor();
    $template = FormTemplate::factory()->published()->create();
    $group = QuestionGroup::factory()->for($template)->create();
    $question = Question::factory()->for($template)->for($group, 'group')->create([
        'answer_type' => AnswerType::Boolean,
        'evidence_requirement' => EvidenceRequirement::Required,
        'is_required' => true,
    ]);

    $survey = Survey::factory()->for($template)->create([
        'user_id' => $surveyor->id,
        'transport_mode_id' => $template->transport_mode_id,
    ]);

    expect(fn () => app(SurveySubmissionService::class)->save($survey, [
        'evaluator_name' => 'Budi',
        'executed_at' => now()->subHour(),
        'answers' => [$question->id => ['value' => true]],
    ], submit: true))->toThrow(ValidationException::class);

    expect($survey->refresh()->status)->toBe(SurveyStatus::Draft);
});

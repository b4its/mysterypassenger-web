<?php

use App\Enums\AnswerType;
use App\Enums\SurveyStatus;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Survey;
use App\Models\TemplateField;
use App\Models\TemplateSection;
use App\Models\TransportMode;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('survey_media');
});

it('mengizinkan surveyor membuat survei baru dengan payload lengkap dan bersifat idempoten', function () {
    $surveyor = User::factory()->surveyor()->create();
    Sanctum::actingAs($surveyor, ['survey:write', 'survey:read']);

    $mode = TransportMode::factory()->create();
    $template = FormTemplate::factory()->for($mode)->published()->create();
    $section = TemplateSection::factory()->for($template)->create();
    $field = TemplateField::factory()->for($template)->create([
        'template_section_id' => $section->id,
        'key' => 'nomor_polisi',
        'label' => 'No Polisi',
    ]);

    $group = QuestionGroup::factory()->for($template)->create();
    $question = Question::factory()->for($template)->for($group, 'group')->create([
        'answer_type' => AnswerType::Boolean,
        'max_score' => 1,
    ]);

    $payload = [
        'idempotency_key' => '01JQ8X4Z2K9F3M7T5R0YB6NWEH',
        'form_template_id' => $template->id,
        'evaluator_name' => 'Budi Surveyor',
        'executed_at' => now()->format('Y-m-d H:i:s'),
        'location_text' => 'Terminal Pulo Gebang',
        'latitude' => -6.214,
        'longitude' => 106.953,
        'summary_note' => 'Semua baik',
        'fields' => [
            'nomor_polisi' => 'B 1234 CD',
        ],
        'answers' => [
            [
                'question_id' => $question->id,
                'value' => true,
                'note' => 'Sesuai standar',
            ],
        ],
    ];

    // Permintaan pertama: 201 Created
    $res1 = $this->postJson(route('api.v2.surveys.store'), $payload);

    $res1->assertCreated()
        ->assertJsonPath('data.evaluator_name', 'Budi Surveyor')
        ->assertJsonPath('data.fields.nomor_polisi', 'B 1234 CD');

    $surveyId = $res1->json('data.id');
    expect(Survey::count())->toBe(1);

    // Permintaan kedua dengan idempotency_key yang sama: 200 OK (idempoten, tidak duplikat)
    $res2 = $this->postJson(route('api.v2.surveys.store'), $payload);

    $res2->assertOk()
        ->assertJsonPath('data.id', $surveyId);

    expect(Survey::count())->toBe(1);
});

it('mengizinkan pembaruan draf survei dan submit', function () {
    $surveyor = User::factory()->surveyor()->create();
    Sanctum::actingAs($surveyor, ['survey:write', 'survey:read']);

    $template = FormTemplate::factory()->published()->withFields()->create();
    $survey = Survey::factory()->for($template)->create([
        'user_id' => $surveyor->id,
        'status' => SurveyStatus::Draft,
    ]);

    $this->patchJson(route('api.v2.surveys.update', $survey), [
        'summary_note' => 'Catatan diperbarui',
    ])->assertOk()
        ->assertJsonPath('data.summary_note', 'Catatan diperbarui');

    $this->postJson(route('api.v2.surveys.submit', $survey))
        ->assertOk()
        ->assertJsonPath('data.status', SurveyStatus::Submitted->value);

    expect($survey->fresh()->status)->toBe(SurveyStatus::Submitted);
});

it('mengizinkan pengunggahan bukti media per pertanyaan dan menolak jika melebihi kuota', function () {
    $surveyor = User::factory()->surveyor()->create();
    Sanctum::actingAs($surveyor, ['survey:write', 'survey:read']);

    $template = FormTemplate::factory()->published()->create();
    $group = QuestionGroup::factory()->for($template)->create();
    $question = Question::factory()->for($template)->for($group, 'group')->create([
        'evidence_max' => 1,
    ]);

    $survey = Survey::factory()->for($template)->create([
        'user_id' => $surveyor->id,
        'status' => SurveyStatus::Draft,
    ]);

    $file1 = UploadedFile::fake()->image('bukti1.jpg');

    $this->postJson(route('api.v2.surveys.answers.media', ['survey' => $survey, 'question' => $question]), [
        'file' => $file1,
    ])->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'path', 'url']]);

    // Unggahan kedua harus gagal karena evidence_max = 1
    $file2 = UploadedFile::fake()->image('bukti2.jpg');
    $this->postJson(route('api.v2.surveys.answers.media', ['survey' => $survey, 'question' => $question]), [
        'file' => $file2,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('melarang surveyor melihat atau mengubah survei surveyor lain', function () {
    $surveyor1 = User::factory()->surveyor()->create();
    $surveyor2 = User::factory()->surveyor()->create();

    $survey2 = Survey::factory()->create(['user_id' => $surveyor2->id]);

    Sanctum::actingAs($surveyor1, ['survey:read', 'survey:write']);

    $this->getJson(route('api.v2.surveys.show', $survey2))
        ->assertForbidden();

    $this->patchJson(route('api.v2.surveys.update', $survey2), [
        'summary_note' => 'Bajak',
    ])->assertForbidden();
});

it('mengizinkan pengunduhan dokumen PDF survei', function () {
    $surveyor = User::factory()->surveyor()->create();
    Sanctum::actingAs($surveyor, ['survey:read']);

    $template = FormTemplate::factory()->published()->withFields()->create();
    $survey = Survey::factory()->for($template)->create(['user_id' => $surveyor->id]);

    $res = $this->get(route('api.v2.surveys.pdf', ['survey' => $survey, 'section' => 'checklist']));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toBe('application/pdf');
});

<?php

use App\Enums\AnswerType;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\QuestionOption;
use App\Models\TemplateAssignment;
use App\Models\TemplateField;
use App\Models\TemplateSection;
use App\Models\TransportMode;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('mengembalikan daftar template formulir terbitan', function () {
    $user = User::factory()->surveyor()->create();
    Sanctum::actingAs($user, ['template:read']);

    $mode = TransportMode::factory()->create();
    $published = FormTemplate::factory()->for($mode)->published()->create(['name' => 'Template Terbit']);
    $draft = FormTemplate::factory()->for($mode)->create(['name' => 'Template Draf']);

    $res = $this->getJson(route('api.v2.templates.index'));

    $res->assertOk()
        ->assertJsonFragment(['name' => 'Template Terbit'])
        ->assertJsonMissing(['name' => 'Template Draf']);
});

it('surveyor hanya melihat template yang ditugaskan jika ada penugasan', function () {
    $surveyor = User::factory()->surveyor()->create();
    Sanctum::actingAs($surveyor, ['template:read']);

    $template1 = FormTemplate::factory()->published()->create(['name' => 'Template Ditugaskan']);
    $template2 = FormTemplate::factory()->published()->create(['name' => 'Template Tidak Ditugaskan']);

    TemplateAssignment::factory()->create([
        'user_id' => $surveyor->id,
        'form_template_id' => $template1->id,
    ]);

    $this->getJson(route('api.v2.templates.index'))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Template Ditugaskan'])
        ->assertJsonMissing(['name' => 'Template Tidak Ditugaskan']);
});

it('mengembalikan struktur template lengkap termasuk section, field, indikator, dan pertanyaan', function () {
    $user = User::factory()->surveyor()->create();
    Sanctum::actingAs($user, ['template:read']);

    $template = FormTemplate::factory()->published()->create();
    $section = TemplateSection::factory()->for($template)->create(['name' => 'Info Umum']);
    TemplateField::factory()->for($template)->create([
        'template_section_id' => $section->id,
        'key' => 'nama_armada',
        'label' => 'Nama Armada',
    ]);

    $group = QuestionGroup::factory()->for($template)->create(['name' => 'Fasilitas']);
    $question = Question::factory()->for($template)->for($group, 'group')->create([
        'text' => 'AC berfungsi baik?',
        'answer_type' => AnswerType::SelectSingle,
    ]);
    QuestionOption::factory()->for($question)->create(['label' => 'Ya', 'value' => 'ya', 'score' => 5]);

    $res = $this->getJson(route('api.v2.templates.show', $template));

    $res->assertOk()
        ->assertJsonPath('data.id', $template->id)
        ->assertJsonFragment(['name' => 'Info Umum'])
        ->assertJsonFragment(['key' => 'nama_armada'])
        ->assertJsonFragment(['name' => 'Fasilitas'])
        ->assertJsonFragment(['text' => 'AC berfungsi baik?'])
        ->assertJsonFragment(['label' => 'Ya', 'value' => 'ya']);
});

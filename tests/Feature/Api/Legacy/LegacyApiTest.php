<?php

use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Survey;
use App\Models\TemplateField;
use App\Models\TemplateSection;
use App\Models\TransportMode;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Siapkan moda kapal dan template default
    $this->mode = TransportMode::firstOrCreate(
        ['slug' => 'kapal-penumpang'],
        ['name' => 'Kapal Penumpang', 'code' => 'SHIP'],
    );

    $this->template = FormTemplate::firstOrCreate(
        ['transport_mode_id' => $this->mode->id, 'slug' => 'mystery-passenger-kapal'],
        ['name' => 'Mystery Passenger — Kapal Penumpang', 'version' => 1, 'status' => 'published'],
    );

    $section = TemplateSection::firstOrCreate(
        ['form_template_id' => $this->template->id, 'name' => 'Info Kapal'],
    );

    foreach ([
        'nama_kapal' => 'Nama Kapal',
        'operator' => 'Operator',
        'asal' => 'Pelabuhan Asal',
        'tujuan' => 'Pelabuhan Tujuan',
    ] as $key => $label) {
        TemplateField::firstOrCreate(
            ['form_template_id' => $this->template->id, 'key' => $key],
            ['template_section_id' => $section->id, 'label' => $label, 'field_type' => 'text'],
        );
    }
});

it('mengizinkan login format legacy v1 dan menyertakan token sanctum', function () {
    $user = User::factory()->surveyor()->create([
        'username' => 'legacypetugas',
        'password' => 'secret123',
    ]);

    $res = $this->postJson('/api/login', [
        'username' => 'legacypetugas',
        'password' => 'secret123',
    ]);

    $res->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.role', 'pengguna')
        ->assertJsonStructure(['token']);
});

it('mengembalikan daftar master pertanyaan dalam format v1', function () {
    $user = User::factory()->surveyor()->create();
    Sanctum::actingAs($user);

    $group = QuestionGroup::firstOrCreate(
        ['form_template_id' => $this->template->id, 'name' => 'Tangibles'],
        ['output_section' => 'checklist', 'weight' => 1],
    );

    Question::firstOrCreate(
        ['form_template_id' => $this->template->id, 'text' => 'Kebersihan kamar mandi'],
        ['question_group_id' => $group->id, 'answer_type' => 'boolean', 'weight' => 1],
    );

    $res = $this->getJson('/api/master-pertanyaan/get');

    $res->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonFragment(['teks' => 'Tangibles'])
        ->assertJsonFragment(['teks' => 'Kebersihan kamar mandi']);

    // Filter checklist
    $this->getJson('/api/master-pertanyaan/ceklist/get')
        ->assertOk()
        ->assertJsonPath('status', 'success');

    // Filter laporan
    $this->getJson('/api/master-pertanyaan/laporan/get')
        ->assertOk()
        ->assertJsonPath('status', 'success');
});

it('mengembalikan 410 Gone untuk endpoint pembuatan pertanyaan dari ponsel', function () {
    $user = User::factory()->surveyor()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/master-pertanyaan/store')
        ->assertStatus(410);

    $this->getJson('/api/list-pertanyaan/1')
        ->assertStatus(410);
});

it('menerima pelaporan format v1 dan menyimpannya ke skema v2', function () {
    $user = User::factory()->surveyor()->create();
    Sanctum::actingAs($user);

    $group = QuestionGroup::firstOrCreate(
        ['form_template_id' => $this->template->id, 'name' => 'Tangibles'],
        ['output_section' => 'checklist'],
    );

    $q = Question::firstOrCreate(
        ['form_template_id' => $this->template->id, 'text' => 'Pelampung tersedia'],
        ['question_group_id' => $group->id, 'answer_type' => 'boolean'],
    );

    $payload = [
        'kapal' => 'KM Kelud',
        'perusahaanPemilik' => 'PT PELNI',
        'pelabuhanAsal' => 'Tanjung Priok',
        'pelabuhanTujuan' => 'Batu Ampar',
        'waktuPelaksanaan' => '2026-10-02 08:00:00',
        'checklist_jawaban' => [
            ['master_pertanyaan_id' => $q->id, 'jawaban' => true, 'catatan' => 'Lengkap'],
        ],
    ];

    $res = $this->postJson('/api/pelaporan/store', $payload);

    $res->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.kapal', 'KM Kelud')
        ->assertJsonPath('data.perusahaanPemilik', 'PT PELNI');

    $survey = Survey::where('evaluator_name', $user->name)->latest()->first();
    expect($survey)->not->toBeNull()
        ->and($survey->field('nama_kapal'))->toBe('KM Kelud')
        ->and($survey->field('operator'))->toBe('PT PELNI')
        ->and($survey->field('asal'))->toBe('Tanjung Priok')
        ->and($survey->field('tujuan'))->toBe('Batu Ampar');
});

it('dapat melihat daftar dan detail pelaporan dalam format v1', function () {
    $user = User::factory()->surveyor()->create();
    Sanctum::actingAs($user);

    $survey = Survey::factory()->for($this->template)->create([
        'user_id' => $user->id,
        'evaluator_name' => $user->name,
    ]);

    $this->getJson('/api/pelaporan/get')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonFragment(['id' => $survey->id]);

    $this->getJson("/api/pelaporan/get/{$survey->id}")
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $survey->id);
});

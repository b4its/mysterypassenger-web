<?php

use App\Enums\FieldType;
use App\Enums\SurveyStatus;
use App\Models\FormTemplate;
use App\Models\Survey;
use App\Models\TemplateField;
use App\Models\User;

it('mengarahkan tamu ke halaman login saat mengakses /app', function () {
    $this->get('/app')->assertRedirect('/login');
    $this->get('/app/surveys')->assertRedirect('/login');
    $this->get('/app/surveys/create')->assertRedirect('/login');
});

it('mengizinkan login web dengan email dan mengarahkan sesuai peran', function () {
    $surveyor = User::factory()->surveyor()->create([
        'email' => 'surveyor.web@example.com',
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);

    $this->post('/login', [
        'login' => 'surveyor.web@example.com',
        'password' => 'password123',
    ])->assertRedirect(route('app.dashboard'));

    $this->assertAuthenticatedAs($surveyor);

    // Logout
    $this->post('/logout')->assertRedirect(route('landing'));
    $this->assertGuest();
});

it('mengizinkan login web dengan username dan mengarahkan admin ke /admin', function () {
    $admin = User::factory()->admin()->create([
        'username' => 'admin_utama',
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);

    $this->post('/login', [
        'login' => 'admin_utama',
        'password' => 'password123',
    ])->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin);
});

it('menolak login untuk akun nonaktif', function () {
    User::factory()->surveyor()->create([
        'email' => 'nonaktif@example.com',
        'password' => bcrypt('password123'),
        'is_active' => false,
    ]);

    $this->post('/login', [
        'login' => 'nonaktif@example.com',
        'password' => 'password123',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('surveyor dapat mengakses dasbor web dan tidak menemukan tautan buat pertanyaan', function () {
    $surveyor = actingAsSurveyor();

    $response = $this->get('/app');

    $response->assertOk()
        ->assertViewIs('app.dashboard')
        ->assertSee('Selamat Datang')
        ->assertSee('Mulai Laporan Baru')
        ->assertSee('Daftar Laporan')
        // Memastikan tidak ada fitur pembuatan pertanyaan di halaman non-filament
        ->assertDontSee('Pengaturan Pertanyaan')
        ->assertDontSee('Template Formulir');
});

it('surveyor tidak bisa mengakses panel filament atau halaman admin', function () {
    actingAsSurveyor();

    $this->get('/admin')->assertForbidden();
    $this->get('/admin/question-setup')->assertForbidden();
    $this->get('/admin/form-templates')->assertForbidden();
    $this->get('/admin/users')->assertForbidden();
});

it('surveyor dapat membuat draf laporan baru, mengisi kuesioner, dan mengirimnya via web', function () {
    $surveyor = actingAsSurveyor();

    $template = FormTemplate::factory()->published()
        ->withFields(['nama_aset' => 'Nama Kapal'])
        ->withChecklist(1, 2)
        ->create();

    $mode = $template->transportMode;

    // 1. Tampilkan form buat laporan
    $this->get('/app/surveys/create')
        ->assertOk()
        ->assertSee($template->name)
        ->assertSee($mode->name);

    // 2. Submit form buat laporan baru
    $postResponse = $this->post('/app/surveys', [
        'form_template_id' => $template->id,
        'evaluator_name' => 'Budi Surveyor',
        'executed_at' => now()->format('Y-m-d\TH:i'),
    ]);

    $survey = Survey::where('form_template_id', $template->id)->first();
    expect($survey)->not->toBeNull()
        ->and($survey->status)->toBe(SurveyStatus::Draft)
        ->and($survey->user_id)->toBe($surveyor->id);

    $postResponse->assertRedirect(route('app.surveys.fill', $survey));

    // 3. Buka halaman isi formulir
    $this->get(route('app.surveys.fill', $survey))
        ->assertOk()
        ->assertSee($survey->code)
        ->assertSee('Nama Kapal');

    // 4. Simpan draf
    [$q1, $q2] = $template->questions()->orderBy('sort_order')->get()->all();
    $this->post(route('app.surveys.update', $survey), [
        'evaluator_name' => 'Budi Surveyor',
        'executed_at' => now()->format('Y-m-d H:i:s'),
        'location_text' => 'Pelabuhan Tanjung Priok',
        'fields' => ['nama_aset' => 'KM Kelud'],
        'answers' => [$q1->id => 1],
        'notes' => [$q1->id => 'Kondisi bersih'],
        'submit' => 0,
    ])->assertRedirect(route('app.surveys.fill', $survey));

    expect($survey->fresh()->status)->toBe(SurveyStatus::Draft)
        ->and($survey->field('nama_aset'))->toBe('KM Kelud');

    // 5. Kirim laporan akhir (semua pertanyaan wajib terjawab)
    $this->post(route('app.surveys.update', $survey), [
        'evaluator_name' => 'Budi Surveyor',
        'executed_at' => now()->format('Y-m-d H:i:s'),
        'location_text' => 'Pelabuhan Tanjung Priok',
        'fields' => ['nama_aset' => 'KM Kelud'],
        'answers' => [
            $q1->id => 1,
            $q2->id => 0,
        ],
        'submit' => 1,
    ])->assertRedirect(route('app.surveys.show', $survey));

    expect($survey->fresh()->status)->toBe(SurveyStatus::Submitted);

    // 6. Lihat halaman detail laporan
    $this->get(route('app.surveys.show', $survey))
        ->assertOk()
        ->assertSee('KM Kelud')
        ->assertSee($survey->code)
        ->assertSee('Terkirim');
});

it('reviewer dapat melihat laporan submitted dan menyetujui atau mengembalikannya via web', function () {
    $reviewer = actingAsReviewer();

    $surveyor = User::factory()->surveyor()->create();
    $template = FormTemplate::factory()->published()->withChecklist(1, 1)->create();

    $survey = Survey::factory()->for($template)->create([
        'user_id' => $surveyor->id,
        'transport_mode_id' => $template->transport_mode_id,
        'status' => SurveyStatus::Submitted,
        'submitted_at' => now(),
    ]);

    // Reviewer melihat detail dan form evaluasi
    $this->get(route('app.surveys.show', $survey))
        ->assertOk()
        ->assertSee('Panel Evaluasi & Persetujuan Laporan')
        ->assertSee('Setujui Laporan');

    // Reviewer menyetujui laporan
    $this->post(route('app.surveys.review', $survey), [
        'action' => 'approve',
    ])->assertRedirect(route('app.surveys.show', $survey));

    expect($survey->fresh()->status)->toBe(SurveyStatus::Approved)
        ->and($survey->fresh()->reviewed_by)->toBe($reviewer->id);
});

it('reviewer wajib mengisi catatan saat mengembalikan laporan', function () {
    actingAsReviewer();

    $survey = Survey::factory()->create([
        'status' => SurveyStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $this->post(route('app.surveys.review', $survey), [
        'action' => 'reject',
        'note' => '',
    ])->assertSessionHasErrors('note');

    // Berikan catatan yang valid
    $this->post(route('app.surveys.review', $survey), [
        'action' => 'reject',
        'note' => 'Mohon lengkapi foto fasilitas keselamatan.',
    ])->assertRedirect(route('app.surveys.show', $survey));

    expect($survey->fresh()->status)->toBe(SurveyStatus::Rejected)
        ->and($survey->fresh()->review_note)->toBe('Mohon lengkapi foto fasilitas keselamatan.');
});

it('surveyor tidak dapat mereview laporannya sendiri', function () {
    $surveyor = actingAsSurveyor();

    $survey = Survey::factory()->create([
        'user_id' => $surveyor->id,
        'status' => SurveyStatus::Submitted,
    ]);

    $this->post(route('app.surveys.review', $survey), [
        'action' => 'approve',
    ])->assertForbidden();
});

it('surveyor tidak dapat melihat laporan milik surveyor lain', function () {
    actingAsSurveyor();

    $otherSurveyor = User::factory()->surveyor()->create();
    $otherSurvey = Survey::factory()->create(['user_id' => $otherSurveyor->id]);

    $this->get(route('app.surveys.show', $otherSurvey))->assertForbidden();
});

it('dapat menampilkan formulir dengan field select berstruktur opsi array tanpa TypeError', function () {
    $surveyor = actingAsSurveyor();

    $template = FormTemplate::factory()->published()->create();

    TemplateField::factory()->create([
        'form_template_id' => $template->id,
        'key' => 'kelas_tiket',
        'label' => 'Kelas Tiket',
        'field_type' => FieldType::Select,
        'options' => [
            ['value' => 'ekonomi', 'label' => 'Ekonomi'],
            ['value' => 'bisnis', 'label' => 'Bisnis'],
        ],
        'is_required' => false,
    ]);

    $survey = Survey::factory()->create([
        'form_template_id' => $template->id,
        'transport_mode_id' => $template->transport_mode_id,
        'user_id' => $surveyor->id,
        'status' => SurveyStatus::Draft,
    ]);

    $this->get(route('app.surveys.fill', $survey))
        ->assertOk()
        ->assertSee('Kelas Tiket')
        ->assertSee('value="ekonomi"', false)
        ->assertSee('Ekonomi')
        ->assertSee('value="bisnis"', false)
        ->assertSee('Bisnis');

    $this->post(route('app.surveys.update', $survey), [
        'evaluator_name' => $survey->evaluator_name,
        'executed_at' => now()->format('Y-m-d H:i:s'),
        'fields' => ['kelas_tiket' => 'bisnis'],
        'submit' => 0,
    ])->assertRedirect(route('app.surveys.fill', $survey));

    expect($survey->fresh()->field('kelas_tiket'))->toBe('bisnis');

    $this->get(route('app.surveys.show', $survey))
        ->assertOk()
        ->assertSee('Kelas Tiket')
        ->assertSee('bisnis');
});

it('menyediakan tombol pratinjau pdf dan modal reviewer pada antarmuka web', function () {
    $surveyor = actingAsSurveyor();

    $survey = Survey::factory()->create([
        'user_id' => $surveyor->id,
        'status' => SurveyStatus::Draft,
    ]);

    // Halaman pengisian (fill) memiliki tombol pratinjau dan modal
    $this->get(route('app.surveys.fill', $survey))
        ->assertOk()
        ->assertSee('Pratinjau PDF')
        ->assertSee('openPdfPreviewModal', false)
        ->assertSee('id="pdfPreviewModal"', false)
        ->assertSee('id="pdfModalIframe"', false)
        ->assertSee('Lembar Ceklist')
        ->assertSee('Laporan Evaluasi')
        ->assertSee('Unduh PDF');

    // Halaman detail (show) juga memiliki tombol pratinjau PDF dan modal
    $this->get(route('app.surveys.show', $survey))
        ->assertOk()
        ->assertSee('Pratinjau PDF')
        ->assertSee('openPdfPreviewModal', false)
        ->assertSee('id="pdfPreviewModal"', false);

    // Halaman daftar laporan (index) memiliki trigger PDF modal
    $this->get(route('app.surveys.index'))
        ->assertOk()
        ->assertSee('openPdfPreviewModal', false)
        ->assertSee('id="pdfPreviewModal"', false);
});

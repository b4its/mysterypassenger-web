<?php

use App\Models\FormTemplate;
use App\Models\Survey;
use App\Models\TransportMode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Test ini menjalankan DDL (drop/create tabel v1) pada koneksi sekunder dan
 * memanggil `artisan migrate:from-v1`. Pada MySQL, DDL memicu IMPLICIT COMMIT
 * sehingga membatalkan transaksi RefreshDatabase — meninggalkan state migrasi
 * yang rusak untuk test berikutnya (tabel hilang → QueryException).
 *
 * Karena itu kami memakai DatabaseMigrations (migrate:fresh per test, TANPA
 * transaksi) alih-alih RefreshDatabase, agar test ini terisolasi penuh dan
 * tidak merusak test lain.
 */
uses(DatabaseMigrations::class);

/**
 * Arahkan koneksi v1_mysql ke database legacy terpisah sebelum tiap test,
 * agar DDL (create/drop tabel v1) tidak mengganggu skema aplikasi.
 */
beforeEach(function () {
    DB::purge('v1_mysql');
    config()->set('database.connections.v1_mysql', array_merge(
        config('database.connections.mysql'),
        ['database' => 'v1_legacy_test'],
    ));

    dropLegacyTables();
});

afterEach(function () {
    try {
        dropLegacyTables();
    } catch (Throwable) {
        // Koneksi sengaja dibuat gagal pada satu test; abaikan.
    } finally {
        DB::purge('v1_mysql');
    }
});

function dropLegacyTables(): void
{
    if (config('database.connections.v1_mysql.database') !== 'v1_legacy_test') {
        return;
    }

    foreach (['jawaban_pelaporan', 'list_pertanyaan', 'master_pertanyaan', 'pelaporan', 'users'] as $table) {
        Schema::connection('v1_mysql')->dropIfExists($table);
    }
}

/**
 * Siapkan skema mirip v1 pada koneksi v1_mysql.
 */
function setupLegacyV1Schema(): void
{
    dropLegacyTables();

    Schema::connection('v1_mysql')->create('users', function ($t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('username')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->default('pengguna');
        $t->timestamps();
    });

    Schema::connection('v1_mysql')->create('pelaporan', function ($t) {
        $t->id();
        $t->unsignedBigInteger('idUsers')->nullable();
        $t->string('evaluator')->nullable();
        $t->string('kapal')->nullable();
        $t->string('perusahaanPemilik')->nullable();
        $t->string('pelabuhanAsal')->nullable();
        $t->string('pelabuhanTujuan')->nullable();
        $t->dateTime('waktuPelaksanaan')->nullable();
        $t->text('lokasi')->nullable();
        $t->string('jenis')->nullable();
        $t->timestamps();
    });

    Schema::connection('v1_mysql')->create('master_pertanyaan', function ($t) {
        $t->id();
        $t->unsignedBigInteger('parent_id')->nullable();
        $t->string('tipe_pertanyaan');
        $t->string('teks_pertanyaan');
        $t->integer('urutan')->default(0);
        $t->timestamps();
    });

    Schema::connection('v1_mysql')->create('list_pertanyaan', function ($t) {
        $t->id();
        $t->unsignedBigInteger('idMasterPertanyaan')->nullable();
        $t->unsignedBigInteger('idUsers')->nullable();
        $t->timestamps();
    });

    Schema::connection('v1_mysql')->create('jawaban_pelaporan', function ($t) {
        $t->id();
        $t->unsignedBigInteger('pelaporan_id');
        $t->unsignedBigInteger('master_pertanyaan_id');
        $t->boolean('kondisi_ceklist')->nullable();
        $t->text('laporan_hasil')->nullable();
        $t->string('bukti_upload')->nullable();
        $t->timestamps();
    });

    $legacy = DB::connection('v1_mysql');

    $legacy->table('users')->insert([
        ['name' => 'Budi Surveyor', 'email' => 'budi@v1.test', 'password' => '$2y$12$abcdefghijklmnopqrstuv', 'role' => 'pengguna', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Admin V1', 'email' => 'adminv1@v1.test', 'password' => '$2y$12$abcdefghijklmnopqrstuv', 'role' => 'admin', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $legacy->table('master_pertanyaan')->insert([
        ['parent_id' => null, 'tipe_pertanyaan' => 'ceklist', 'teks_pertanyaan' => 'Tangibles', 'urutan' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['parent_id' => null, 'tipe_pertanyaan' => 'laporan', 'teks_pertanyaan' => 'Laporan Kegiatan', 'urutan' => 2, 'created_at' => now(), 'updated_at' => now()],
        ['parent_id' => 1, 'tipe_pertanyaan' => 'ceklist', 'teks_pertanyaan' => 'Kebersihan ruang penumpang', 'urutan' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['parent_id' => 1, 'tipe_pertanyaan' => 'ceklist', 'teks_pertanyaan' => 'Kondisi toilet', 'urutan' => 2, 'created_at' => now(), 'updated_at' => now()],
        ['parent_id' => 2, 'tipe_pertanyaan' => 'laporan', 'teks_pertanyaan' => 'Uraian kondisi umum', 'urutan' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $legacy->table('pelaporan')->insert([
        'idUsers' => 1, 'evaluator' => 'Budi Surveyor', 'kapal' => 'KM Lama', 'perusahaanPemilik' => 'PT Lama',
        'pelabuhanAsal' => 'Priok', 'pelabuhanTujuan' => 'Perak', 'waktuPelaksanaan' => '2025-10-01 08:00:00',
        'lokasi' => 'Pelabuhan Priok', 'jenis' => 'kapal', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $legacy->table('jawaban_pelaporan')->insert([
        ['pelaporan_id' => 1, 'master_pertanyaan_id' => 3, 'kondisi_ceklist' => 1, 'laporan_hasil' => null, 'created_at' => now(), 'updated_at' => now()],
        ['pelaporan_id' => 1, 'master_pertanyaan_id' => 4, 'kondisi_ceklist' => 0, 'laporan_hasil' => null, 'created_at' => now(), 'updated_at' => now()],
        ['pelaporan_id' => 1, 'master_pertanyaan_id' => 5, 'kondisi_ceklist' => null, 'laporan_hasil' => 'Kondisi umum baik.', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $legacy->table('list_pertanyaan')->insert([
        ['idMasterPertanyaan' => 3, 'idUsers' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);
}

afterEach(function () {
    if (Schema::connection('v1_mysql')->hasTable('users')) {
        foreach (['jawaban_pelaporan', 'list_pertanyaan', 'master_pertanyaan', 'pelaporan', 'users'] as $table) {
            Schema::connection('v1_mysql')->dropIfExists($table);
        }
    }
});

it('melaporkan jumlah baris pada dry-run tanpa menulis apa pun', function () {
    setupLegacyV1Schema();

    $this->artisan('migrate:from-v1', ['--connection' => 'v1_mysql', '--dry-run' => true])
        ->assertSuccessful();

    expect(TransportMode::query()->count())->toBe(0)
        ->and(Survey::query()->count())->toBe(0);
});

it('memigrasikan data v1 ke skema v2 secara lengkap', function () {
    setupLegacyV1Schema();

    $this->artisan('migrate:from-v1', ['--connection' => 'v1_mysql'])
        ->assertSuccessful();

    // Moda & template dibuat.
    expect(TransportMode::where('slug', 'kapal-penumpang')->exists())->toBeTrue();
    $template = FormTemplate::where('slug', 'mystery-passenger-kapal')->first();
    expect($template)->not->toBeNull();

    // Field profil dari kolom hardcode.
    expect($template->fields()->pluck('key')->all())
        ->toEqualCanonicalizing(['nama_kapal', 'operator', 'asal', 'tujuan']);

    // Pertanyaan terpetakan (2 ceklist + 1 laporan).
    expect($template->questions()->count())->toBe(3)
        ->and($template->questionGroups()->count())->toBe(2);

    // Pengguna & penugasan.
    expect(User::where('email', 'budi@v1.test')->exists())->toBeTrue()
        ->and($template->assignments()->count())->toBe(1);

    // Survei + jawaban + field values + snapshot.
    $survey = Survey::first();
    expect($survey)->not->toBeNull()
        ->and($survey->meta)->not->toBeNull()
        ->and($survey->field('nama_kapal'))->toBe('KM Lama')
        ->and($survey->field('operator'))->toBe('PT Lama')
        ->and($survey->answers()->count())->toBe(3)
        ->and($survey->status->value)->toBe('approved');

    // Jawaban boolean & uraian terpetakan benar.
    expect($survey->answers()->where('value_boolean', true)->count())->toBe(1)
        ->and($survey->answers()->where('value_boolean', false)->count())->toBe(1)
        ->and($survey->answers()->where('value_text', 'Kondisi umum baik.')->count())->toBe(1);
});

it('gagal dengan pesan jelas bila koneksi v1 tidak tersedia', function () {
    DB::purge('v1_mysql');

    $this->artisan('migrate:from-v1', ['--connection' => 'v1_mysql_tidak_ada'])
        ->expectsOutputToContain('Tidak dapat terhubung')
        ->assertFailed();
});

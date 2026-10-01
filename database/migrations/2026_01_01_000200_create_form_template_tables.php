<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Jenis transportasi: fully custom, tanpa hardcode ────────────────
        Schema::create('transport_modes', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // "Kapal Penumpang", "Bus AKAP", "KRL"
            $table->string('slug')->unique();             // "kapal-penumpang"
            $table->string('code', 20)->nullable();       // "SHIP", "BUS" — untuk nomor dokumen
            $table->text('description')->nullable();
            $table->string('icon')->default('heroicon-o-truck');
            $table->string('color')->default('primary');  // nama warna Filament
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();         // ruang perluasan per moda
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Template formulir, berversi ─────────────────────────────────────
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_mode_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('version')->default(1);
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();      // draft|published|archived
            $table->boolean('scoring_enabled')->default(true);
            $table->string('scoring_strategy')->default('weighted');  // weighted|simple|none
            $table->decimal('passing_score', 5, 2)->nullable();       // persen, mis. 75.00
            $table->unsignedTinyInteger('max_evidence_per_answer')->default(5);
            $table->string('locale', 10)->default('id');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['transport_mode_id', 'slug', 'version']);
            $table->index(['transport_mode_id', 'status']);
        });

        // ── Bagian profil perjalanan ────────────────────────────────────────
        Schema::create('template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
            $table->string('name');                       // "Informasi Kapal & Pemilik", "Rute & Lokasi"
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('columns')->default(2);   // kolom grid Filament
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['form_template_id', 'sort_order']);
        });

        // ── Field profil dinamis: pengganti kapal/pelabuhanAsal/dll ─────────
        Schema::create('template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key', 64);                    // "nama_kapal", "operator", "asal"
            $table->string('label');                      // "Nama Kapal" / "Nama Bus" / "Nomor KA"
            $table->string('field_type')->default('text'); // lihat enum FieldType
            $table->string('placeholder')->nullable();
            $table->string('helper_text')->nullable();
            $table->json('options')->nullable();          // untuk select/radio: [{value,label}]
            $table->json('default_value')->nullable();
            $table->json('validation_rules')->nullable(); // ["max:255","regex:/.../"]
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);   // muncul sebagai filter tabel
            $table->boolean('show_in_table')->default(false);   // muncul sebagai kolom tabel
            $table->boolean('show_in_pdf')->default(true);      // muncul di kop PDF
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_template_id', 'key']);
            $table->index(['form_template_id', 'sort_order']);
        });

        // ── Indikator / dimensi, nestable tanpa batas ───────────────────────
        Schema::create('question_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()
                ->constrained('question_groups')->cascadeOnDelete();
            $table->string('name');                       // "Tangibles", "Reliability"
            $table->text('description')->nullable();
            $table->string('output_section')->default('checklist'); // checklist|report|both
            $table->decimal('weight', 8, 4)->default(1);
            $table->unsignedTinyInteger('depth')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['form_template_id', 'parent_id', 'sort_order'], 'qg_template_parent_order_idx');
            $table->index(['form_template_id', 'output_section']);
        });

        // ── Pertanyaan / sub-indikator ──────────────────────────────────────
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_group_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32)->nullable();       // "TAN-01"
            $table->text('text');                         // teks pertanyaan
            $table->text('helper_text')->nullable();
            $table->string('answer_type')->default('boolean');  // lihat enum AnswerType
            $table->json('options')->nullable();          // config tipe: {"scale":{"min":1,"max":5}}
            $table->boolean('is_required')->default(true);
            $table->string('evidence_requirement')->default('optional'); // none|optional|required
            $table->unsignedTinyInteger('evidence_max')->default(3);
            $table->boolean('allow_note')->default(true);
            $table->decimal('weight', 8, 4)->default(1);
            $table->decimal('max_score', 8, 2)->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // Conditional logic: tampilkan pertanyaan ini hanya bila kondisi terpenuhi
            $table->foreignId('depends_on_question_id')->nullable()
                ->constrained('questions')->nullOnDelete();
            $table->string('depends_on_operator', 20)->nullable();  // equals|not_equals|in|filled
            $table->json('depends_on_value')->nullable();

            $table->timestamps();

            $table->index(['form_template_id', 'question_group_id', 'sort_order'], 'q_template_group_order_idx');
            $table->index(['question_group_id', 'is_active']);
        });

        // ── Opsi jawaban terstruktur (boleh punya skor) ─────────────────────
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('value', 64);
            $table->string('label');
            $table->decimal('score', 8, 2)->default(0);
            $table->boolean('is_compliant')->nullable();  // untuk pelaporan kepatuhan
            $table->string('color')->nullable();          // badge di tabel/infolist
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['question_id', 'value']);
            $table->index(['question_id', 'sort_order']);
        });

        // ── Penugasan template ke surveyor (pengganti list_pertanyaan) ──────
        Schema::create('template_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['form_template_id', 'user_id']);
            $table->index(['user_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_assignments');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('question_groups');
        Schema::dropIfExists('template_fields');
        Schema::dropIfExists('template_sections');
        Schema::dropIfExists('form_templates');
        Schema::dropIfExists('transport_modes');
    }
};

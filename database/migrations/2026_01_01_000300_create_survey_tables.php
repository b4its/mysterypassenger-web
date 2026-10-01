<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();               // dipakai untuk path media & URL publik
            $table->string('idempotency_key', 32)->nullable()->unique(); // dok.12: cegah duplikat retry API
            $table->string('code', 40)->unique();         // "SHIP/2026/03/0001" — pengganti ROW_NUMBER()
            $table->foreignId('transport_mode_id')->constrained()->restrictOnDelete();
            $table->foreignId('form_template_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('template_version');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();   // surveyor
            $table->string('evaluator_name');             // nama di blok tanda tangan PDF
            $table->dateTime('executed_at')->index();
            $table->string('location_text', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->default('draft')->index();  // SurveyStatus

            // Skor
            $table->decimal('total_score', 10, 2)->nullable();
            $table->decimal('max_score', 10, 2)->nullable();
            $table->decimal('score_percentage', 5, 2)->nullable();
            $table->boolean('is_passed')->nullable();

            $table->text('summary_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            // Snapshot struktur template saat submit → PDF tetap reproducible
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['transport_mode_id', 'status', 'executed_at'], 'survey_mode_status_date_idx');
            $table->index(['user_id', 'status']);
        });

        Schema::create('survey_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_field_id')->constrained()->cascadeOnDelete();
            $table->string('field_key', 64);              // denormalisasi, aman walau field dihapus
            $table->string('field_label');                // denormalisasi untuk PDF
            $table->json('value')->nullable();            // nilai kanonik
            $table->string('value_text', 500)->nullable(); // salinan untuk search/sort/index
            $table->timestamps();

            $table->unique(['survey_id', 'template_field_id']);
            $table->index(['field_key', 'value_text'], 'sfv_key_text_idx');
        });

        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_group_id')->constrained()->cascadeOnDelete(); // denormalisasi
            $table->string('answer_type');                // snapshot tipe saat dijawab

            $table->boolean('value_boolean')->nullable();
            $table->decimal('value_number', 12, 4)->nullable();
            $table->text('value_text')->nullable();
            $table->json('value_json')->nullable();       // select_multiple

            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->boolean('is_compliant')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['survey_id', 'question_id']);
            $table->index(['survey_id', 'question_group_id']);
        });

        Schema::create('survey_answer_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_answer_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('survey_media');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['survey_answer_id', 'sort_order']);
        });

        Schema::create('report_settings', function (Blueprint $table) {
            $table->id();
            // null = pengaturan global/default
            $table->foreignId('transport_mode_id')->nullable()->unique()
                ->constrained()->cascadeOnDelete();
            $table->string('organization_name')->default('Mystery Passenger');
            $table->string('checklist_title')->default('LEMBAR CEKLIST KEGIATAN MYSTERY PASSENGER');
            $table->string('report_title')->default('LAPORAN KEGIATAN MYSTERY PASSENGER');
            $table->string('logo_path')->nullable();
            $table->json('letterhead_lines')->nullable();  // baris alamat/instansi di kop
            $table->text('footer_note')->nullable();
            $table->string('paper_size', 20)->default('a4');       // a4|legal|letter
            $table->string('orientation', 20)->default('landscape');
            $table->boolean('show_photos')->default(true);
            $table->unsignedTinyInteger('photos_per_row')->default(2);
            $table->boolean('show_scores')->default(true);
            $table->json('signature_blocks')->nullable();  // [{label,name,position}]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_settings');
        Schema::dropIfExists('survey_answer_media');
        Schema::dropIfExists('survey_answers');
        Schema::dropIfExists('survey_field_values');
        Schema::dropIfExists('surveys');
    }
};

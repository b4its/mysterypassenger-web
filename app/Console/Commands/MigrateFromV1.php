<?php

namespace App\Console\Commands;

use App\Enums\AnswerType;
use App\Enums\FieldType;
use App\Enums\OutputSection;
use App\Enums\SurveyStatus;
use App\Enums\TemplateStatus;
use App\Enums\UserRole;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAnswerMedia;
use App\Models\SurveyFieldValue;
use App\Models\TemplateField;
use App\Models\TemplateSection;
use App\Models\TransportMode;
use App\Models\User;
use App\Services\SurveySnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateFromV1 extends Command
{
    protected $signature = 'migrate:from-v1
        {--connection=v1_mysql : Nama koneksi database v1}
        {--media-path= : Path absolut direktori public/media/jawaban di server v1}
        {--dry-run : Hanya laporkan jumlah baris, tidak menulis apa pun}';

    protected $description = 'Migrasi data Mystery Passenger v1 ke skema v2 (opsional).';

    /** @var array<string, int> */
    private array $report = [];

    public function handle(SurveySnapshotService $snapshots): int
    {
        $connection = (string) $this->option('connection');
        $dryRun = (bool) $this->option('dry-run');

        try {
            DB::connection($connection)->getPdo();
        } catch (\Throwable $e) {
            $this->error("Tidak dapat terhubung ke koneksi '{$connection}': {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info($dryRun ? '=== DRY RUN (tidak menulis) ===' : '=== MIGRASI v1 → v2 ===');

        $legacy = DB::connection($connection);

        $counts = [
            'users' => $legacy->table('users')->count(),
            'pelaporan' => $legacy->table('pelaporan')->count(),
            'master_pertanyaan' => $legacy->table('master_pertanyaan')->count(),
            'jawaban_pelaporan' => $legacy->table('jawaban_pelaporan')->count(),
            'list_pertanyaan' => $legacy->table('list_pertanyaan')->count(),
        ];

        $this->table(['Tabel v1', 'Jumlah baris'], collect($counts)->map(fn ($n, $t) => [$t, $n])->all());

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry run selesai. Tidak ada data yang ditulis.');
            $this->line('Sumber pelaporan: '.$legacy->table('pelaporan')->distinct()->pluck('jenis')->filter()->implode(', '));

            return self::SUCCESS;
        }

        DB::transaction(function () use ($legacy, $snapshots) {
            $mode = $this->ensureTransportMode();
            $template = $this->ensureTemplate($mode);
            $this->migrateUsers($legacy);
            $questionMap = $this->ensureQuestions($template, $legacy);
            $this->migrateAssignments($legacy, $template);
            $this->migrateSurveys($legacy, $mode, $template, $snapshots);
        });

        $this->newLine();
        $this->table(['Hasil', 'Jumlah'], collect($this->report)->map(fn ($n, $k) => [$k, $n])->all());
        $this->info('Migrasi selesai.');

        return self::SUCCESS;
    }

    private function ensureTransportMode(): TransportMode
    {
        $mode = TransportMode::firstOrCreate(
            ['slug' => 'kapal-penumpang'],
            [
                'name' => 'Kapal Penumpang',
                'code' => 'SHIP',
                'description' => 'Hasil migrasi dari Mystery Passenger v1.',
                'icon' => 'heroicon-o-lifebuoy',
                'color' => 'info',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );

        $this->report['transport_modes (dibuat/ditemukan)'] = 1;

        return $mode;
    }

    private function ensureTemplate(TransportMode $mode): FormTemplate
    {
        $template = FormTemplate::firstOrCreate(
            ['transport_mode_id' => $mode->id, 'slug' => 'mystery-passenger-kapal', 'version' => 1],
            [
                'name' => 'Mystery Passenger — Kapal Penumpang (v1)',
                'status' => TemplateStatus::Published,
                'scoring_enabled' => true,
                'scoring_strategy' => 'weighted',
                'passing_score' => 75,
                'published_at' => now(),
            ],
        );

        // Field profil yang menggantikan kolom hardcode v1.
        $section = TemplateSection::firstOrCreate(
            ['form_template_id' => $template->id, 'name' => 'Informasi Kapal & Rute'],
            ['columns' => 2, 'sort_order' => 1],
        );

        foreach ([
            ['nama_kapal', 'Nama Kapal'],
            ['operator', 'Perusahaan Pemilik'],
            ['asal', 'Pelabuhan Asal'],
            ['tujuan', 'Pelabuhan Tujuan'],
        ] as $i => [$key, $label]) {
            TemplateField::firstOrCreate(
                ['form_template_id' => $template->id, 'key' => $key],
                [
                    'template_section_id' => $section->id,
                    'label' => $label,
                    'field_type' => FieldType::Text,
                    'show_in_pdf' => true,
                    'show_in_table' => in_array($key, ['nama_kapal', 'asal', 'tujuan'], true),
                    'sort_order' => $i + 1,
                ],
            );
        }

        $this->report['form_templates (dibuat/ditemukan)'] = 1;

        return $template;
    }

    private function migrateUsers($legacy): void
    {
        $created = 0;

        foreach ($legacy->table('users')->get() as $row) {
            if (User::withTrashed()->where('email', $row->email)->exists()) {
                continue;
            }

            User::create([
                'name' => $row->name,
                'email' => $row->email,
                'username' => $row->username ?? null,
                'password' => $row->password ?: Hash::make(Str::random(32)),
                'role' => ($row->role ?? null) === 'admin' ? UserRole::Admin : UserRole::Surveyor,
                'is_active' => true,
            ]);

            $created++;
        }

        $this->report['users (dibuat)'] = $created;
    }

    /**
     * Petakan master_pertanyaan → question_groups (induk) + questions (anak).
     *
     * @return array<int, int> peta id master_pertanyaan → id question
     */
    private function ensureQuestions(FormTemplate $template, $legacy): array
    {
        $groupOrder = 0;
        $questionMap = [];
        $groups = $legacy->table('master_pertanyaan')->whereNull('parent_id')->orderBy('urutan')->get();

        foreach ($groups as $group) {
            $section = ($group->tipe_pertanyaan ?? 'ceklist') === 'laporan'
                ? OutputSection::Report
                : OutputSection::Checklist;

            $questionGroup = QuestionGroup::firstOrCreate(
                ['form_template_id' => $template->id, 'name' => $group->teks_pertanyaan, 'parent_id' => null],
                ['output_section' => $section, 'sort_order' => ++$groupOrder],
            );

            $children = $legacy->table('master_pertanyaan')->where('parent_id', $group->id)->orderBy('urutan')->get();

            foreach ($children as $i => $child) {
                $question = Question::firstOrCreate(
                    [
                        'form_template_id' => $template->id,
                        'question_group_id' => $questionGroup->id,
                        'text' => $child->teks_pertanyaan,
                    ],
                    [
                        'answer_type' => $child->tipe_pertanyaan === 'laporan'
                            ? AnswerType::TextLong
                            : AnswerType::Boolean,
                        'max_score' => $child->tipe_pertanyaan === 'laporan' ? 0 : 1,
                        'sort_order' => $i + 1,
                        'evidence_requirement' => 'optional',
                    ],
                );

                $questionMap[(int) $child->id] = $question->id;
            }
        }

        $this->report['question_groups (dibuat/ditemukan)'] = $template->questionGroups()->count();
        $this->report['questions (dibuat/ditemukan)'] = $template->questions()->count();

        return $questionMap;
    }

    private function migrateAssignments($legacy, FormTemplate $template): void
    {
        $created = 0;
        $rows = $legacy->table('list_pertanyaan')
            ->whereNotNull('idUsers')
            ->select('idUsers')
            ->distinct()
            ->pluck('idUsers');

        foreach ($rows as $legacyUserId) {
            $legacyUser = $legacy->table('users')->where('id', $legacyUserId)->first();

            if (! $legacyUser) {
                continue;
            }

            $user = User::where('email', $legacyUser->email)->first();

            if (! $user) {
                continue;
            }

            $template->assignments()->firstOrCreate(['user_id' => $user->id]);
            $created++;
        }

        $this->report['template_assignments (dibuat/ditemukan)'] = $created;
    }

    private function migrateSurveys($legacy, TransportMode $mode, FormTemplate $template, SurveySnapshotService $snapshots): void
    {
        $surveys = 0;
        $answers = 0;
        $media = 0;
        $mediaPath = $this->option('media-path');

        $legacy->table('pelaporan')->orderBy('id')->chunk(100, function ($rows) use (
            $legacy, $mode, $template, $snapshots, $mediaPath, &$surveys, &$answers, &$media
        ) {
            foreach ($rows as $row) {
                $user = $row->idUsers
                    ? User::where('email', $legacy->table('users')->where('id', $row->idUsers)->value('email'))->first()
                    : User::where('role', UserRole::Admin)->first();

                $survey = Survey::create([
                    'transport_mode_id' => $mode->id,
                    'form_template_id' => $template->id,
                    'template_version' => $template->version,
                    'user_id' => $user?->id ?? User::where('role', UserRole::Admin)->value('id'),
                    'evaluator_name' => $row->evaluator ?: ($user?->name ?? 'Evaluator'),
                    'executed_at' => $row->waktuPelaksanaan ?: $row->created_at ?: now(),
                    'location_text' => $row->lokasi,
                    'status' => SurveyStatus::Approved,
                    'submitted_at' => $row->created_at ?? now(),
                    'summary_note' => null,
                ]);

                // Field profil dari kolom hardcode v1.
                foreach ([
                    'nama_kapal' => $row->kapal,
                    'operator' => $row->perusahaanPemilik,
                    'asal' => $row->pelabuhanAsal,
                    'tujuan' => $row->pelabuhanTujuan,
                ] as $key => $value) {
                    $field = TemplateField::where('form_template_id', $template->id)->where('key', $key)->first();

                    if (! $field) {
                        continue;
                    }

                    SurveyFieldValue::create([
                        'survey_id' => $survey->id,
                        'template_field_id' => $field->id,
                        'field_key' => $key,
                        'field_label' => $field->label,
                        'value' => $value,
                        'value_text' => is_scalar($value) ? (string) $value : null,
                    ]);
                }

                foreach ($legacy->table('jawaban_pelaporan')->where('pelaporan_id', $row->id)->get() as $jawaban) {
                    $legacyText = $legacy->table('master_pertanyaan')
                        ->where('id', $jawaban->master_pertanyaan_id)
                        ->value('teks_pertanyaan');

                    $question = $legacyText
                        ? Question::where('form_template_id', $template->id)->where('text', $legacyText)->first()
                        : null;

                    if (! $question) {
                        continue;
                    }

                    $answer = SurveyAnswer::create([
                        'survey_id' => $survey->id,
                        'question_id' => $question->id,
                        'question_group_id' => $question->question_group_id,
                        'answer_type' => $question->answer_type,
                        'value_boolean' => $jawaban->kondisi_ceklist,
                        'value_text' => $jawaban->laporan_hasil,
                        'max_score' => $question->max_score,
                        'answered_at' => $jawaban->created_at ?? now(),
                    ]);

                    $answers++;

                    if (filled($jawaban->bukti_upload)) {
                        $path = $this->copyLegacyMedia($jawaban->bukti_upload, $survey, $answer, $mediaPath);

                        if ($path) {
                            SurveyAnswerMedia::create([
                                'survey_answer_id' => $answer->id,
                                'disk' => 'survey_media',
                                'path' => $path,
                                'original_name' => basename($jawaban->bukti_upload),
                                'sort_order' => 0,
                            ]);
                            $media++;
                        } else {
                            $this->warn("Bukti tidak ditemukan/dilewati: {$jawaban->bukti_upload}");
                        }
                    }
                }

                // Snapshot agar PDF hasil migrasi reproducible.
                $survey->update(['meta' => $snapshots->build($survey->refresh())]);
                $surveys++;
            }
        });

        $this->report['surveys (baru)'] = $surveys;
        $this->report['survey_answers (baru)'] = $answers;
        $this->report['survey_answer_media (baru)'] = $media;
    }

    private function copyLegacyMedia(string $relative, Survey $survey, SurveyAnswer $answer, ?string $mediaPath): ?string
    {
        if (! $mediaPath) {
            return null;
        }

        $absolute = rtrim($mediaPath, '/').'/'.ltrim($relative, '/');

        if (! is_file($absolute)) {
            return null;
        }

        $extension = pathinfo($absolute, PATHINFO_EXTENSION) ?: 'jpg';
        $target = "{$survey->uuid}/{$answer->id}/".Str::ulid().".{$extension}";

        Storage::disk('survey_media')->put($target, file_get_contents($absolute));

        return $target;
    }
}

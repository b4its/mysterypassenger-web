<?php

namespace App\Http\Controllers\Api\Legacy;

use App\Enums\SurveyStatus;
use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use App\Models\Survey;
use App\Models\TransportMode;
use App\Services\SurveySubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegacyPelaporanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $surveys = Survey::query()
            ->visibleTo($user)
            ->with(['fieldValues', 'answers'])
            ->latest('executed_at')
            ->get();

        $data = $surveys->map(fn (Survey $s) => $this->formatLegacySurvey($s));

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $survey = Survey::with(['fieldValues', 'answers.question'])->findOrFail($id);
        $this->authorize('view', $survey);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatLegacySurvey($survey),
        ]);
    }

    public function store(Request $request, SurveySubmissionService $service): JsonResponse
    {
        $user = $request->user();

        $mode = TransportMode::where('slug', 'kapal-penumpang')->first()
            ?? TransportMode::firstOrFail();

        $template = FormTemplate::query()
            ->where('transport_mode_id', $mode->id)
            ->published()
            ->latest('version')
            ->with(['fields', 'questions'])
            ->firstOrFail();

        $this->authorize('createFrom', [Survey::class, $template]);

        $executedAt = $request->input('waktuPelaksanaan')
            ? Carbon::parse($request->input('waktuPelaksanaan'))
            : now();

        $survey = Survey::create([
            'transport_mode_id' => $mode->id,
            'form_template_id' => $template->id,
            'template_version' => $template->version,
            'user_id' => $user->id,
            'evaluator_name' => (string) ($request->input('evaluator') ?? $user->name),
            'executed_at' => $executedAt,
            'location_text' => (string) ($request->input('lokasi') ?? $request->input('pelabuhanAsal')),
            'status' => SurveyStatus::Draft,
        ]);

        $fields = [
            'nama_kapal' => $request->input('kapal'),
            'operator' => $request->input('perusahaanPemilik'),
            'asal' => $request->input('pelabuhanAsal'),
            'tujuan' => $request->input('pelabuhanTujuan'),
        ];

        // Format jawaban v1: checklist_jawaban (array [{master_pertanyaan_id, jawaban}] atau key => val)
        $checklistInput = $request->input('checklist_jawaban', []);
        if (is_string($checklistInput)) {
            $checklistInput = json_decode($checklistInput, true) ?? [];
        }

        $laporanInput = $request->input('laporan_jawaban', []);
        if (is_string($laporanInput)) {
            $laporanInput = json_decode($laporanInput, true) ?? [];
        }

        $answersMap = [];

        foreach ((array) $checklistInput as $item) {
            $qId = is_array($item) ? ($item['master_pertanyaan_id'] ?? $item['id'] ?? null) : null;
            $val = is_array($item) ? ($item['jawaban'] ?? $item['value'] ?? null) : $item;
            if ($qId) {
                $answersMap[(int) $qId] = [
                    'value' => (bool) $val,
                    'note' => is_array($item) ? ($item['catatan'] ?? null) : null,
                ];
            }
        }

        foreach ((array) $laporanInput as $item) {
            $qId = is_array($item) ? ($item['master_pertanyaan_id'] ?? $item['id'] ?? null) : null;
            $val = is_array($item) ? ($item['jawaban'] ?? $item['laporan_hasil'] ?? $item['value'] ?? null) : $item;
            if ($qId) {
                $answersMap[(int) $qId] = [
                    'value' => (string) $val,
                    'note' => is_array($item) ? ($item['catatan'] ?? null) : null,
                ];
            }
        }

        $state = [
            'evaluator_name' => $survey->evaluator_name,
            'executed_at' => $survey->executed_at,
            'location_text' => $survey->location_text,
            'fields' => $fields,
            'answers' => $answersMap,
        ];

        $service->save($survey, $state, submit: true);

        // Upload bukti jika dikirim di multipart request. Klien legacy kadang
        // mengirim array; ambil berkas pertama agar tidak fatal error.
        $file = $request->file('bukti_upload');

        if (is_array($file)) {
            $file = collect($file)->filter()->first();
        }

        if ($file) {
            $path = $file->store($survey->uuid, 'survey_media');
            $firstAnswer = $survey->answers()->first();
            if ($firstAnswer) {
                $firstAnswer->media()->create([
                    'disk' => 'survey_media',
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'sort_order' => 0,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan berhasil disimpan',
            'data' => $this->formatLegacySurvey($survey->fresh(['fieldValues', 'answers'])),
        ], 201);
    }

    public function media(Request $request, string $path): StreamedResponse|BinaryFileResponse
    {
        // Path media disimpan sebagai "{survey_uuid}/{filename}". Wajib dipastikan
        // berkas memang milik survei yang boleh dilihat user ini, agar surveyor
        // tidak dapat mengunduh bukti milik surveyor lain (IDOR).
        $uuid = explode('/', $path, 2)[0] ?? '';

        $survey = Survey::where('uuid', $uuid)->first();

        abort_if($survey === null, 404, 'Berkas media tidak ditemukan.');
        $this->authorize('view', $survey);

        $disk = Storage::disk('survey_media');
        abort_unless($disk->exists($path), 404, 'Berkas media tidak ditemukan.');

        return $disk->response($path);
    }

    private function formatLegacySurvey(Survey $survey): array
    {
        return [
            'id' => $survey->id,
            'idUsers' => $survey->user_id,
            'evaluator' => $survey->evaluator_name,
            'kapal' => $survey->field('nama_kapal'),
            'perusahaanPemilik' => $survey->field('operator'),
            'pelabuhanAsal' => $survey->field('asal'),
            'pelabuhanTujuan' => $survey->field('tujuan'),
            'waktuPelaksanaan' => $survey->executed_at?->format('Y-m-d H:i:s'),
            'lokasi' => $survey->location_text,
            'kode_dokumen' => $survey->code,
            'total_skor' => $survey->total_score,
            'status' => $survey->status->value,
            'created_at' => $survey->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\V2;

use App\Enums\SurveyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\StoreSurveyRequest;
use App\Http\Requests\Api\V2\UpdateSurveyRequest;
use App\Http\Resources\Api\V2\SurveyResource;
use App\Models\FormTemplate;
use App\Models\Survey;
use App\Services\SurveySubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class SurveyController extends Controller
{
    /**
     * Relasi yang wajib dimuat sebelum serialisasi SurveyResource.
     * `question.questionOptions` & `question.group` dibutuhkan oleh
     * SurveyAnswerResource::displayValue()/typedValue() (mis. rating, pilihan
     * ganda) sehingga preventLazyLoading tidak melempar exception.
     *
     * @var array<int, string>
     */
    private const SURVEY_RELATIONS = [
        'transportMode',
        'formTemplate',
        'surveyor',
        'reviewer',
        'fieldValues',
        'answers.media',
        'answers.question.group',
        'answers.question.questionOptions',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Survey::query()
            ->visibleTo($request->user())
            ->with(['transportMode', 'formTemplate', 'surveyor', 'reviewer'])
            ->latest('executed_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('transport_mode_id')) {
            $query->where('transport_mode_id', $request->integer('transport_mode_id'));
        }

        if ($request->filled('from')) {
            $query->where('executed_at', '>=', Carbon::parse($request->input('from'))->startOfDay());
        }

        if ($request->filled('until')) {
            $query->where('executed_at', '<=', Carbon::parse($request->input('until'))->endOfDay());
        }

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        return SurveyResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Survey $survey): SurveyResource
    {
        $this->authorize('view', $survey);

        $survey->load(self::SURVEY_RELATIONS);

        return SurveyResource::make($survey);
    }

    public function store(StoreSurveyRequest $request, SurveySubmissionService $service): JsonResponse
    {
        // Idempotensi: kembalikan yang sudah ada bila idempotency_key cocok.
        // WAJIB dibatasi ke user pembuat agar kunci milik surveyor lain tidak
        // membocorkan survei mereka (IDOR).
        $existing = Survey::where('idempotency_key', $request->string('idempotency_key'))
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            $existing->load(self::SURVEY_RELATIONS);

            return SurveyResource::make($existing)->response()->setStatusCode(200);
        }

        /** @var FormTemplate $template */
        $template = FormTemplate::published()->findOrFail($request->integer('form_template_id'));

        $this->authorize('createFrom', [Survey::class, $template]);

        $survey = Survey::create([
            'idempotency_key' => $request->string('idempotency_key')->toString(),
            'transport_mode_id' => $template->transport_mode_id,
            'form_template_id' => $template->id,
            'template_version' => $template->version,
            'user_id' => $request->user()->id,
            'evaluator_name' => (string) $request->input('evaluator_name'),
            'executed_at' => $request->input('executed_at'),
            'location_text' => $request->input('location_text'),
            'latitude' => $request->input('latitude') !== null ? (float) $request->input('latitude') : null,
            'longitude' => $request->input('longitude') !== null ? (float) $request->input('longitude') : null,
            'summary_note' => $request->input('summary_note'),
            'status' => SurveyStatus::Draft,
        ]);

        $service->save($survey, $request->toFormState(), submit: $request->boolean('submit'));

        $survey = $survey->fresh(self::SURVEY_RELATIONS);

        return SurveyResource::make($survey)->response()->setStatusCode(201);
    }

    public function update(UpdateSurveyRequest $request, Survey $survey, SurveySubmissionService $service): SurveyResource
    {
        $this->authorize('update', $survey);

        $service->save($survey, $request->toFormState(), submit: $request->boolean('submit'));

        $survey = $survey->fresh(self::SURVEY_RELATIONS);

        return SurveyResource::make($survey);
    }

    public function submit(Request $request, Survey $survey, SurveySubmissionService $service): SurveyResource
    {
        $this->authorize('update', $survey);

        $service->save($survey, [], submit: true);

        $survey = $survey->fresh(self::SURVEY_RELATIONS);

        return SurveyResource::make($survey);
    }
}

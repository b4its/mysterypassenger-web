<?php

namespace App\Http\Controllers\App;

use App\Enums\SurveyStatus;
use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use App\Models\Survey;
use App\Models\TransportMode;
use App\Services\SurveySubmissionService;
use App\Support\AnswerTypes\AnswerTypeRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pembuatan & pengisian laporan (non-Filament) untuk surveyor.
 *
 * Tidak ada pengelolaan pertanyaan di sini — struktur formulir hanya dibaca
 * (read-only) dari template yang sudah diterbitkan admin.
 */
class SurveyFillController extends Controller
{
    public function __construct(private AnswerTypeRegistry $registry) {}

    /** Langkah 1: pilih moda + template lalu buat draf. */
    public function create(): View
    {
        $this->authorize('create', Survey::class);

        $user = auth()->user();

        $templates = FormTemplate::query()
            ->published()
            ->with('transportMode')
            ->when(
                $user->isSurveyor(),
                fn ($q) => $q->where(function ($sub) use ($user) {
                    $sub->whereHas('assignments', fn ($a) => $a->where('user_id', $user->id))
                        ->orWhereDoesntHave('assignments');
                }),
            )
            ->orderBy('name')
            ->get()
            ->groupBy('transport_mode_id');

        $modes = TransportMode::query()
            ->where('is_active', true)
            ->whereIn('id', $templates->keys())
            ->orderBy('sort_order')
            ->get();

        return view('app.surveys.create', [
            'modes' => $modes,
            'templatesByMode' => $templates,
        ]);
    }

    /** Simpan draf baru, lalu arahkan ke halaman pengisian. */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Survey::class);

        $data = $request->validate([
            'form_template_id' => ['required', 'integer', 'exists:form_templates,id'],
            'evaluator_name' => ['required', 'string', 'max:160'],
            'executed_at' => ['required', 'date'],
        ]);

        $template = FormTemplate::published()->findOrFail($data['form_template_id']);

        $this->authorize('createFrom', [Survey::class, $template]);

        $survey = Survey::create([
            'transport_mode_id' => $template->transport_mode_id,
            'form_template_id' => $template->id,
            'template_version' => $template->version,
            'user_id' => auth()->id(),
            'evaluator_name' => $data['evaluator_name'],
            'executed_at' => $data['executed_at'],
            'status' => SurveyStatus::Draft,
        ]);

        return redirect()->route('app.surveys.fill', $survey)
            ->with('status', "Draf {$survey->code} dibuat. Silakan isi laporan.");
    }

    /** Halaman pengisian dinamis. */
    public function fill(Survey $survey): View
    {
        $this->authorize('update', $survey);

        $survey->load([
            'transportMode',
            'formTemplate.sections',
            'formTemplate.fields.section',
            'formTemplate.rootGroups.questions.questionOptions',
            'formTemplate.rootGroups.children.questions.questionOptions',
            'fieldValues',
            'answers.media',
            'answers.question',
        ]);

        return view('app.surveys.fill', [
            'survey' => $survey,
            'template' => $survey->formTemplate,
            'values' => $this->currentValues($survey),
        ]);
    }

    /** Simpan draf atau kirim. */
    public function update(Request $request, Survey $survey, SurveySubmissionService $service): RedirectResponse
    {
        $this->authorize('update', $survey);

        $survey->loadMissing(['answers.media', 'formTemplate.questions']);

        $submit = $request->boolean('submit');

        $service->save($survey, $this->stateFromRequest($request, $survey), submit: $submit);

        return $submit
            ? redirect()->route('app.surveys.show', $survey)->with('status', 'Laporan berhasil dikirim.')
            : redirect()->route('app.surveys.fill', $survey)->with('status', 'Draf disimpan.');
    }

    /**
     * Ubah request HTML menjadi state kontrak SurveySubmissionService.
     *
     * @return array{fields: array<string,mixed>, answers: array<int,array<string,mixed>>}
     */
    private function stateFromRequest(Request $request, Survey $survey): array
    {
        $fields = (array) $request->input('fields', []);
        $answersInput = (array) $request->input('answers', []);
        $notes = (array) $request->input('notes', []);

        $answers = [];

        foreach ($survey->formTemplate->questions as $question) {
            $raw = $answersInput[$question->id] ?? null;

            // select_multiple datang sebagai array; boolean sebagai "1"/"0".
            if ($question->answer_type->value === 'select_multiple') {
                $value = array_values((array) $raw);
            } elseif ($question->answer_type->value === 'boolean') {
                $value = $raw === null || $raw === '' ? null : (bool) $raw;
            } else {
                $value = $raw === '' ? null : $raw;
            }

            $uploadedPaths = collect((array) $request->file("media.{$question->id}", []))
                ->filter()
                ->map(fn ($file) => $file->store($survey->uuid, 'survey_media'))
                ->all();

            $existing = $survey->answers->firstWhere('question_id', $question->id)
                ?->media->pluck('path')->all() ?? [];

            $answers[$question->id] = [
                'value' => $value,
                'note' => $notes[$question->id] ?? null,
                'media' => array_merge($existing, $uploadedPaths),
            ];
        }

        return [
            'evaluator_name' => $request->input('evaluator_name', $survey->evaluator_name),
            'executed_at' => $request->input('executed_at', $survey->executed_at),
            'location_text' => $request->input('location_text'),
            'summary_note' => $request->input('summary_note'),
            'fields' => $fields,
            'answers' => $answers,
        ];
    }

    /**
     * Nilai saat ini untuk mengisi ulang form (state → tampilan).
     *
     * @return array<string, mixed>
     */
    public function currentValues(Survey $survey): array
    {
        return [
            'fields' => $survey->fieldValues
                ->mapWithKeys(fn ($fv) => [$fv->field_key => $fv->value])
                ->all(),
            'answers' => $survey->answers
                ->mapWithKeys(fn ($a) => [$a->question_id => $this->registry->for($a->answer_type)->toFormState($a)])
                ->all(),
            'notes' => $survey->answers
                ->mapWithKeys(fn ($a) => [$a->question_id => $a->note])
                ->all(),
            'media' => $survey->answers
                ->mapWithKeys(fn ($a) => [$a->question_id => $a->media->all()])
                ->all(),
        ];
    }
}

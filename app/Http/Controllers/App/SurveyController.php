<?php

namespace App\Http\Controllers\App;

use App\Enums\SurveyStatus;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Daftar & detail laporan (non-Filament) untuk surveyor & reviewer.
 *
 * Surveyor hanya melihat survei miliknya (scope visibleTo). Reviewer/admin
 * melihat seluruh survei yang diizinkan policy.
 */
class SurveyController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = Survey::query()
            ->visibleTo($user)
            ->with(['transportMode', 'formTemplate'])
            ->latest('executed_at');

        if ($request->filled('status')) {
            $status = SurveyStatus::tryFrom((string) $request->input('status'));
            if ($status) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('q')) {
            $term = (string) $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('evaluator_name', 'like', "%{$term}%");
            });
        }

        return view('app.surveys.index', [
            'surveys' => $query->paginate(12)->withQueryString(),
            'statuses' => SurveyStatus::cases(),
            'filters' => [
                'status' => $request->input('status'),
                'q' => $request->input('q'),
            ],
        ]);
    }

    public function show(Survey $survey): View
    {
        $this->authorize('view', $survey);

        $survey->load([
            'transportMode',
            'formTemplate',
            'surveyor',
            'reviewer',
            'fieldValues',
            'answers.question.group',
            'answers.question.questionOptions',
            'answers.media',
        ]);

        return view('app.surveys.show', [
            'survey' => $survey,
        ]);
    }
}

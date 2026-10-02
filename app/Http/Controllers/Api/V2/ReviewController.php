<?php

namespace App\Http\Controllers\Api\V2;

use App\Enums\SurveyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\TransitionSurveyRequest;
use App\Http\Resources\Api\V2\SurveyResource;
use App\Models\Survey;
use App\Services\SurveyStateMachine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isReviewer(), 403, 'Hanya reviewer dan admin yang dapat mengakses antrean review.');

        $status = $request->input('status', SurveyStatus::Submitted->value);

        $query = Survey::query()
            ->where('status', $status)
            ->with(['transportMode', 'formTemplate', 'surveyor'])
            ->latest('submitted_at');

        if ($request->filled('transport_mode_id')) {
            $query->where('transport_mode_id', $request->integer('transport_mode_id'));
        }

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        return SurveyResource::collection($query->paginate($perPage));
    }

    public function transition(TransitionSurveyRequest $request, Survey $survey, SurveyStateMachine $stateMachine): SurveyResource
    {
        $this->authorize('review', $survey);

        $status = SurveyStatus::from($request->string('status')->toString());

        $stateMachine->transition(
            $survey,
            $status,
            $request->input('note'),
        );

        $survey->fresh([
            'transportMode',
            'formTemplate',
            'surveyor',
            'reviewer',
            'fieldValues',
            'answers.media',
            'answers.question',
        ]);

        return SurveyResource::make($survey);
    }
}

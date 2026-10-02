<?php

namespace App\Http\Controllers\App;

use App\Enums\SurveyStatus;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Services\SurveyStateMachine;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SurveyReviewController extends Controller
{
    public function __construct(private SurveyStateMachine $stateMachine) {}

    /**
     * Setujui atau kembalikan laporan survei oleh Reviewer/Admin.
     */
    public function review(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorize('review', $survey);

        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'note' => ['nullable', 'string', 'required_if:action,reject', 'max:2000'],
        ], [
            'note.required_if' => 'Catatan review wajib diisi bila laporan dikembalikan/ditolak.',
        ]);

        $targetStatus = $data['action'] === 'approve'
            ? SurveyStatus::Approved
            : SurveyStatus::Rejected;

        try {
            $this->stateMachine->transition($survey, $targetStatus, $data['note'] ?? null);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $targetStatus === SurveyStatus::Approved
            ? "Laporan {$survey->code} telah disetujui."
            : "Laporan {$survey->code} telah dikembalikan kepada surveyor.";

        return redirect()->route('app.surveys.show', $survey)->with('status', $message);
    }
}

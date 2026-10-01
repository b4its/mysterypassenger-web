<?php

namespace App\Services;

use App\Enums\SurveyStatus;
use App\Models\Survey;
use DomainException;
use Illuminate\Support\Facades\Auth;

class SurveyStateMachine
{
    /**
     * Eksekusi transisi status survei dengan validasi.
     *
     * @throws DomainException bila transisi tidak diizinkan.
     */
    public function transition(Survey $survey, SurveyStatus $to, ?string $note = null): Survey
    {
        $from = $survey->status;

        if (! in_array($to, $from->allowedTransitions(), true)) {
            throw new DomainException(
                "Transisi status tidak diizinkan: {$from->value} → {$to->value}."
            );
        }

        if ($to === SurveyStatus::Rejected && blank($note)) {
            throw new DomainException('Catatan review wajib diisi saat mengembalikan survei.');
        }

        $payload = ['status' => $to];

        if ($to === SurveyStatus::Submitted) {
            $payload['submitted_at'] = now();
        }

        if (in_array($to, [SurveyStatus::Approved, SurveyStatus::Rejected, SurveyStatus::UnderReview], true)) {
            $payload['reviewed_by'] = Auth::id();
            $payload['reviewed_at'] = now();
        }

        if (filled($note)) {
            $payload['review_note'] = $note;
        }

        $survey->update($payload);

        return $survey->refresh();
    }
}

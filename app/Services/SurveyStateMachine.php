<?php

namespace App\Services;

use App\Enums\SurveyStatus;
use App\Models\Survey;
use DomainException;
use Filament\Notifications\Notification;
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
        $survey = $survey->refresh();

        $this->notifySurveyor($survey, $to, $note);

        return $survey;
    }

    /** Beri tahu surveyor pemilik saat status berubah oleh reviewer/admin. */
    private function notifySurveyor(Survey $survey, SurveyStatus $to, ?string $note): void
    {
        $notifiable = match ($to) {
            SurveyStatus::Approved, SurveyStatus::Rejected, SurveyStatus::UnderReview,
            SurveyStatus::Submitted => $survey->surveyor,
            default => null,
        };

        if (! $notifiable) {
            return;
        }

        $title = match ($to) {
            SurveyStatus::Approved => 'Survei disetujui',
            SurveyStatus::Rejected => 'Survei dikembalikan untuk revisi',
            SurveyStatus::UnderReview => 'Survei sedang direview',
            SurveyStatus::Submitted => 'Survei terkirim',
            default => 'Status survei diperbarui',
        };

        $body = "Survei {$survey->code} — {$survey->transportMode?->name}.";
        $color = in_array($to, [SurveyStatus::Approved], true) ? 'success'
            : (in_array($to, [SurveyStatus::Rejected], true) ? 'danger' : 'info');

        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->status($color)
            ->icon($to->getIcon());

        if (filled($note)) {
            $notification->body($body."\nCatatan: {$note}");
        }

        $notification->sendToDatabase($notifiable);
    }
}

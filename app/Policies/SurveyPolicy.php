<?php

namespace App\Policies;

use App\Enums\SurveyStatus;
use App\Models\FormTemplate;
use App\Models\Survey;
use App\Models\User;

class SurveyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Survey $survey): bool
    {
        return $user->isAdmin() || $user->isReviewer() || $survey->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSurveyor();
    }

    public function update(User $user, Survey $survey): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isReviewer()) {
            return $survey->status->isLocked();
        }

        return $survey->user_id === $user->id && $survey->status->isEditableBySurveyor();
    }

    public function delete(User $user, Survey $survey): bool
    {
        return $user->isAdmin()
            || ($survey->user_id === $user->id && $survey->status === SurveyStatus::Draft);
    }

    public function restore(User $user, Survey $survey): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Survey $survey): bool
    {
        return $user->isAdmin();
    }

    public function review(User $user, Survey $survey): bool
    {
        return $user->isAdmin() || $user->isReviewer();
    }

    public function print(User $user, Survey $survey): bool
    {
        return $this->view($user, $survey);
    }

    /** Surveyor hanya boleh memakai template yang ditugaskan kepadanya. */
    public function createFrom(User $user, FormTemplate $template): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isSurveyor()) {
            return false;
        }

        return $template->assignments()->where('user_id', $user->id)->exists()
            || $template->assignments()->doesntExist();
    }
}

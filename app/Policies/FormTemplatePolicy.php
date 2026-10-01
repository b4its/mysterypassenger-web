<?php

namespace App\Policies;

use App\Models\FormTemplate;
use App\Models\User;

class FormTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isReviewer();
    }

    public function view(User $user, FormTemplate $template): bool
    {
        return $user->isAdmin() || $user->isReviewer();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, FormTemplate $template): bool
    {
        return $user->isAdmin() && $template->isEditable();
    }

    public function delete(User $user, FormTemplate $template): bool
    {
        return $user->isAdmin() && $template->surveys()->doesntExist();
    }

    public function restore(User $user, FormTemplate $template): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, FormTemplate $template): bool
    {
        return $user->isAdmin();
    }
}

<?php

namespace App\Policies;

use App\Models\TransportMode;
use App\Models\User;

class TransportModePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isReviewer();
    }

    public function view(User $user, TransportMode $mode): bool
    {
        return $user->isAdmin() || $user->isReviewer();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TransportMode $mode): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TransportMode $mode): bool
    {
        return $user->isAdmin() && $mode->surveys()->doesntExist();
    }

    public function restore(User $user, TransportMode $mode): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, TransportMode $mode): bool
    {
        return $user->isAdmin();
    }
}

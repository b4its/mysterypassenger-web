<?php

namespace App\Policies;

use App\Models\ReportSetting;
use App\Models\User;

class ReportSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ReportSetting $setting): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ReportSetting $setting): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ReportSetting $setting): bool
    {
        return $user->isAdmin();
    }
}

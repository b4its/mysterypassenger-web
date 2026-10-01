<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/** Login sebagai role tertentu dan kembalikan user-nya. */
function actingAsRole(UserRole $role = UserRole::Admin): User
{
    $user = User::factory()->create(['role' => $role, 'is_active' => true]);

    test()->actingAs($user);

    return $user;
}

function actingAsAdmin(): User
{
    return actingAsRole(UserRole::Admin);
}

function actingAsReviewer(): User
{
    return actingAsRole(UserRole::Reviewer);
}

function actingAsSurveyor(): User
{
    return actingAsRole(UserRole::Surveyor);
}

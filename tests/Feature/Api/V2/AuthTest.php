<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('mengizinkan login dengan email atau username valid dan mengembalikan token', function () {
    $user = User::factory()->create([
        'username' => 'petugas1',
        'email' => 'petugas1@example.test',
        'password' => 'secret123',
        'role' => UserRole::Surveyor,
        'is_active' => true,
    ]);

    // Login via username
    $response = $this->postJson(route('api.v2.auth.login'), [
        'username' => 'petugas1',
        'password' => 'secret123',
        'device_name' => 'Redmi Note 12',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'token',
                'user' => ['id', 'name', 'username', 'email', 'role'],
            ],
        ]);

    expect($user->fresh()->last_login_at)->not->toBeNull();

    // Login via email
    $this->postJson(route('api.v2.auth.login'), [
        'login' => 'petugas1@example.test',
        'password' => 'secret123',
    ])->assertOk();
});

it('menolak login dengan password salah', function () {
    User::factory()->create([
        'email' => 'user@example.test',
        'password' => 'benar123',
    ]);

    $this->postJson(route('api.v2.auth.login'), [
        'login' => 'user@example.test',
        'password' => 'salah123',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['login']);
});

it('menolak login akun non-aktif', function () {
    User::factory()->create([
        'email' => 'nonaktif@example.test',
        'password' => 'secret123',
        'is_active' => false,
    ]);

    $this->postJson(route('api.v2.auth.login'), [
        'login' => 'nonaktif@example.test',
        'password' => 'secret123',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['login']);
});

it('memberikan token ability sesuai role pengguna', function () {
    $admin = User::factory()->admin()->create(['password' => 'secret123']);
    $reviewer = User::factory()->reviewer()->create(['password' => 'secret123']);
    $surveyor = User::factory()->surveyor()->create(['password' => 'secret123']);

    $resAdmin = $this->postJson(route('api.v2.auth.login'), ['login' => $admin->email, 'password' => 'secret123']);
    $resRev = $this->postJson(route('api.v2.auth.login'), ['login' => $reviewer->email, 'password' => 'secret123']);
    $resSurv = $this->postJson(route('api.v2.auth.login'), ['login' => $surveyor->email, 'password' => 'secret123']);

    expect($admin->tokens()->first()->abilities)->toEqual(['*'])
        ->and($reviewer->tokens()->first()->abilities)->toEqual(['survey:read', 'survey:review', 'template:read'])
        ->and($surveyor->tokens()->first()->abilities)->toEqual(['survey:read', 'survey:write', 'template:read']);
});

it('dapat melihat profil me dan mencabut token saat logout', function () {
    $user = User::factory()->surveyor()->create();
    Sanctum::actingAs($user, ['survey:read']);

    $this->getJson(route('api.v2.auth.me'))
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.role', 'surveyor');

    $this->postJson(route('api.v2.auth.logout'))
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

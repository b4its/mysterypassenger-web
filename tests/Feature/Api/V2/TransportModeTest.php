<?php

use App\Models\TransportMode;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('mengembalikan daftar moda transportasi aktif', function () {
    $user = User::factory()->surveyor()->create();
    Sanctum::actingAs($user, ['template:read']);

    TransportMode::factory()->create(['name' => 'Kapal Cepat', 'is_active' => true]);
    TransportMode::factory()->create(['name' => 'Moda Nonaktif', 'is_active' => false]);

    $res = $this->getJson(route('api.v2.transport-modes.index'));

    $res->assertOk()
        ->assertJsonFragment(['name' => 'Kapal Cepat'])
        ->assertJsonMissing(['name' => 'Moda Nonaktif']);
});

it('dapat memfilter moda berdasarkan updated_since', function () {
    $user = User::factory()->surveyor()->create();
    Sanctum::actingAs($user, ['template:read']);

    $old = TransportMode::factory()->create(['updated_at' => now()->subDays(5)]);
    $recent = TransportMode::factory()->create(['updated_at' => now()]);

    $this->getJson(route('api.v2.transport-modes.index', ['updated_since' => now()->subDay()->toISOString()]))
        ->assertOk()
        ->assertJsonFragment(['name' => $recent->name])
        ->assertJsonMissing(['name' => $old->name]);
});

it('mengabaikan updated_since yang tidak valid tanpa error 500', function () {
    $user = User::factory()->surveyor()->create();
    Sanctum::actingAs($user, ['template:read']);

    $mode = TransportMode::factory()->create(['is_active' => true]);

    $this->getJson(route('api.v2.transport-modes.index', ['updated_since' => 'bukan-tanggal']))
        ->assertOk()
        ->assertJsonFragment(['name' => $mode->name]);
});

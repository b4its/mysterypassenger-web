<?php

use App\Filament\Resources\TransportModes\Pages\CreateTransportMode;
use App\Filament\Resources\TransportModes\Pages\ListTransportModes;
use App\Filament\Resources\TransportModes\TransportModeResource;
use App\Models\TransportMode;

use function Pest\Livewire\livewire;

beforeEach(fn () => actingAsAdmin());

it('menampilkan daftar moda', function () {
    $modes = TransportMode::factory()->count(3)->create();

    livewire(ListTransportModes::class)
        ->assertCanSeeTableRecords($modes);
});

it('membuat moda baru beserta slug otomatis', function () {
    livewire(CreateTransportMode::class)
        ->fillForm(['name' => 'Kapal Penyeberangan'])
        ->assertSchemaStateSet(['slug' => 'kapal-penyeberangan'])
        ->fillForm(['code' => 'FERRY'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TransportMode::where('slug', 'kapal-penyeberangan')->exists())->toBeTrue();
});

it('menolak slug duplikat', function () {
    TransportMode::factory()->create(['slug' => 'bus-akap']);

    livewire(CreateTransportMode::class)
        ->fillForm(['name' => 'Bus AKAP', 'slug' => 'bus-akap'])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('menolak akses surveyor', function () {
    actingAsSurveyor();

    expect(TransportModeResource::canAccess())->toBeFalse();
});

it('mengizinkan reviewer melihat daftar (read-only)', function () {
    actingAsReviewer();

    expect(TransportModeResource::canAccess())->toBeTrue();
});

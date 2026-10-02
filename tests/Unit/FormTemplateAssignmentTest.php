<?php

use App\Models\FormTemplate;
use App\Models\User;

it('mengekspos atribut pivot penugasan (due_at, starts_at, notes)', function () {
    $template = FormTemplate::factory()->create();
    $surveyor = User::factory()->surveyor()->create();

    $template->assignedUsers()->attach($surveyor->id, [
        'starts_at' => now()->subDay(),
        'due_at' => now()->addDays(3),
        'notes' => 'Fokus rute utara',
    ]);

    $assigned = $template->assignedUsers()->firstWhere('users.id', $surveyor->id);

    expect($assigned)->not->toBeNull()
        ->and($assigned->pivot->due_at)->not->toBeNull()
        ->and($assigned->pivot->notes)->toBe('Fokus rute utara');
});

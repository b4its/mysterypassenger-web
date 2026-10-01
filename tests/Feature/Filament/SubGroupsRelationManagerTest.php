<?php

use App\Filament\Resources\FormTemplates\Pages\EditFormTemplate;
use App\Filament\Resources\FormTemplates\RelationManagers\SubGroupsRelationManager;
use App\Models\FormTemplate;
use App\Models\QuestionGroup;

use function Pest\Livewire\livewire;

beforeEach(fn () => actingAsAdmin());

it('menampilkan relasi sub-indikator pada halaman edit template', function () {
    $template = FormTemplate::factory()->create();

    livewire(SubGroupsRelationManager::class, [
        'ownerRecord' => $template,
        'pageClass' => EditFormTemplate::class,
    ])->assertOk();
});

it('membuat sub-indikator dengan induk dan menghitung kedalaman otomatis', function () {
    $template = FormTemplate::factory()->create();
    $root = QuestionGroup::factory()->for($template)->create(['depth' => 0]);

    livewire(SubGroupsRelationManager::class, [
        'ownerRecord' => $template,
        'pageClass' => EditFormTemplate::class,
    ])
        ->callTableAction('create', data: [
            'name' => 'Sub Indikator A',
            'parent_id' => $root->id,
            'output_section' => 'checklist',
            'weight' => 1,
            'sort_order' => 1,
        ])
        ->assertHasNoTableActionErrors();

    $child = $template->questionGroups()->where('name', 'Sub Indikator A')->first();

    expect($child)->not->toBeNull()
        ->and($child->parent_id)->toBe($root->id)
        ->and($child->depth)->toBe(1);
});

it('menolak menjadikan turunan sendiri sebagai induk', function () {
    $template = FormTemplate::factory()->create();
    $root = QuestionGroup::factory()->for($template)->create(['depth' => 0]);
    $child = QuestionGroup::factory()->for($template)->create([
        'parent_id' => $root->id, 'depth' => 1,
    ]);

    // Induk yang ditawarkan tidak boleh memuat dirinya sendiri.
    livewire(SubGroupsRelationManager::class, [
        'ownerRecord' => $template,
        'pageClass' => EditFormTemplate::class,
    ])
        ->mountTableAction('edit', $child)
        ->assertOk();
});

it('mengunci relasi ketika template sudah diterbitkan', function () {
    $template = FormTemplate::factory()->published()->create();

    $rm = new SubGroupsRelationManager;
    $rm->ownerRecord = $template;

    expect($rm->isReadOnly())->toBeTrue();
});

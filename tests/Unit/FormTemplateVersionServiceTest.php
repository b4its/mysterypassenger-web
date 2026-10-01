<?php

use App\Enums\TemplateStatus;
use App\Models\FormTemplate;
use App\Models\Survey;
use App\Services\FormTemplateVersionService;

it('mengkloning seluruh struktur ke versi baru berstatus draft', function () {
    $old = FormTemplate::factory()->published()->withChecklist(2, 3)->withFields()->create();

    $new = app(FormTemplateVersionService::class)->createNewVersion($old);

    expect($new->version)->toBe(2)
        ->and($new->status)->toBe(TemplateStatus::Draft)
        ->and($new->published_at)->toBeNull()
        ->and($new->id)->not->toBe($old->id);

    expect($new->questionGroups()->count())->toBe($old->questionGroups()->count())
        ->and($new->questions()->count())->toBe($old->questions()->count())
        ->and($new->fields()->count())->toBe($old->fields()->count());
});

it('memetakan ulang depends_on_question_id ke pertanyaan hasil klon', function () {
    $old = FormTemplate::factory()->published()->withChecklist(1, 2)->create();

    [$q1, $q2] = $old->questions->values();

    $q2->update([
        'depends_on_question_id' => $q1->id,
        'depends_on_operator' => 'equals',
        'depends_on_value' => [true],
    ]);

    $new = app(FormTemplateVersionService::class)->createNewVersion($old);

    $newQ1 = $new->questions()->where('sort_order', 1)->first();
    $newQ2 = $new->questions()->where('sort_order', 2)->first();

    expect($newQ2->depends_on_question_id)
        ->not->toBe($q1->id)
        ->toBe($newQ1->id);
});

it('tidak menyalin survei milik versi lama', function () {
    $old = FormTemplate::factory()->published()->withChecklist(1, 1)->create();

    Survey::factory()->for($old)->create();

    $new = app(FormTemplateVersionService::class)->createNewVersion($old);

    expect($new->surveys()->count())->toBe(0)
        ->and($old->surveys()->count())->toBe(1);
});

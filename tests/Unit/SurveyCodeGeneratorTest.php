<?php

use App\Models\FormTemplate;
use App\Models\Survey;
use App\Models\TransportMode;

it('membuat kode dengan prefiks moda dan urutan bulanan', function () {
    $mode = TransportMode::factory()->create(['code' => 'SHIP']);
    $template = FormTemplate::factory()->for($mode)->create();

    $first = Survey::factory()->for($mode)->for($template)
        ->create(['executed_at' => '2026-03-05 08:00:00']);
    $second = Survey::factory()->for($mode)->for($template)
        ->create(['executed_at' => '2026-03-19 08:00:00']);

    expect($first->code)->toBe('SHIP/2026/03/0001')
        ->and($second->code)->toBe('SHIP/2026/03/0002');
});

it('mereset urutan pada bulan baru', function () {
    $mode = TransportMode::factory()->create(['code' => 'BUS']);
    $template = FormTemplate::factory()->for($mode)->create();

    $march = Survey::factory()->for($mode)->for($template)
        ->create(['executed_at' => '2026-03-31 08:00:00']);
    $april = Survey::factory()->for($mode)->for($template)
        ->create(['executed_at' => '2026-04-01 08:00:00']);

    expect($march->code)->toBe('BUS/2026/03/0001')
        ->and($april->code)->toBe('BUS/2026/04/0001');
});

it('memakai kode moda default ketika kolom code kosong', function () {
    $mode = TransportMode::factory()->create(['code' => null]);
    $template = FormTemplate::factory()->for($mode)->create();

    $survey = Survey::factory()->for($mode)->for($template)
        ->create(['executed_at' => '2026-05-10 08:00:00']);

    expect($survey->code)->toBe('MP/2026/05/0001');
});

it('memberi setiap survei uuid unik', function () {
    $a = Survey::factory()->create();
    $b = Survey::factory()->create();

    expect($a->uuid)->not->toBe($b->uuid)
        ->and(strlen($a->uuid))->toBe(26);
});

<?php

use App\Models\FormTemplate;
use App\Models\Survey;
use App\Services\SurveySnapshotService;

it('menyimpan label field dan struktur indikator ke meta', function () {
    $template = FormTemplate::factory()->published()
        ->withFields(['nama_kapal' => 'Nama Kapal'])
        ->withChecklist(2, 3)
        ->create();

    $survey = Survey::factory()->for($template)->create();
    $meta = app(SurveySnapshotService::class)->build($survey);

    expect($meta)->toHaveKeys(['captured_at', 'template', 'sections', 'fields', 'groups'])
        ->and($meta['fields'])->toHaveCount(1)
        ->and($meta['fields'][0]['key'])->toBe('nama_kapal')
        ->and($meta['fields'][0]['label'])->toBe('Nama Kapal')
        ->and($meta['groups'])->toHaveCount(2)
        ->and($meta['groups'][0]['questions'])->toHaveCount(3);
});

it('menjaga urutan indikator dan pertanyaan di dalam snapshot', function () {
    $template = FormTemplate::factory()->published()->withChecklist(3, 2)->create();
    $survey = Survey::factory()->for($template)->create();

    $meta = app(SurveySnapshotService::class)->build($survey);

    $names = collect($meta['groups'])->pluck('name')->all();
    $sortOrders = collect($meta['groups'])->pluck('sort_order')->all();

    expect($names)->toBe(['Indikator 1', 'Indikator 2', 'Indikator 3'])
        ->and($sortOrders)->toBe([1, 2, 3]);
});

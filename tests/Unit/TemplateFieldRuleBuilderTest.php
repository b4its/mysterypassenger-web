<?php

use App\Enums\FieldType;
use App\Models\TemplateField;
use App\Support\TemplateFieldRuleBuilder;

it('menghasilkan aturan untuk semua tipe field tanpa error', function () {
    $builder = app(TemplateFieldRuleBuilder::class);

    foreach (FieldType::cases() as $type) {
        $field = new TemplateField(['field_type' => $type, 'key' => 'x', 'label' => 'X']);

        expect($builder->rulesFor($field))->toBeArray()->not->toBeEmpty();
    }
});

it('memetakan validation_rules format peta key→parameter menjadi rule yang benar', function () {
    $builder = app(TemplateFieldRuleBuilder::class);

    $field = new TemplateField([
        'field_type' => FieldType::Text,
        'key' => 'kode',
        'label' => 'Kode',
        'validation_rules' => ['max' => 100, 'regex' => '/^[A-Z]+$/'],
    ]);

    $rules = $builder->rulesFor($field);

    expect($rules)->toContain('max:100')
        ->and($rules)->toContain('regex:/^[A-Z]+$/')
        // Nilai mentah (100 / regex mentah) tidak boleh lolos sebagai rule terpisah.
        ->and($rules)->not->toContain(100)
        ->and($rules)->not->toContain('/^[A-Z]+$/');
});

it('meneruskan validation_rules format daftar apa adanya', function () {
    $builder = app(TemplateFieldRuleBuilder::class);

    $field = new TemplateField([
        'field_type' => FieldType::Text,
        'key' => 'kode',
        'label' => 'Kode',
        'validation_rules' => ['max:50', 'email'],
    ]);

    expect($builder->rulesFor($field))->toContain('max:50')->toContain('email');
});

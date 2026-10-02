<?php

use App\Models\TemplateField;

it('mengembalikan optionPairs dengan benar dari berbagai format options', function () {
    // Format list of value/label arrays (Filament repeater / seeder format)
    $field1 = new TemplateField([
        'options' => [
            ['value' => 'ekonomi', 'label' => 'Ekonomi'],
            ['value' => 'bisnis', 'label' => 'Bisnis'],
        ],
    ]);
    expect($field1->optionPairs())->toBe([
        'ekonomi' => 'Ekonomi',
        'bisnis' => 'Bisnis',
    ]);

    // Format associative array [value => label]
    $field2 = new TemplateField([
        'options' => [
            'opt1' => 'Opsi 1',
            'opt2' => 'Opsi 2',
        ],
    ]);
    expect($field2->optionPairs())->toBe([
        'opt1' => 'Opsi 1',
        'opt2' => 'Opsi 2',
    ]);

    // Format indexed array of strings ['Val1', 'Val2']
    $field3 = new TemplateField([
        'options' => ['Ekonomi', 'Bisnis'],
    ]);
    expect($field3->optionPairs())->toBe([
        'Ekonomi' => 'Ekonomi',
        'Bisnis' => 'Bisnis',
    ]);

    // Format null
    $field4 = new TemplateField(['options' => null]);
    expect($field4->optionPairs())->toBe([]);
});

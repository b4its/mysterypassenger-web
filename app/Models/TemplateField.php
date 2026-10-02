<?php

namespace App\Models;

use App\Enums\FieldType;
use App\Observers\TemplateFieldObserver;
use Database\Factories\TemplateFieldFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(TemplateFieldObserver::class)]
class TemplateField extends Model
{
    /** @use HasFactory<TemplateFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'form_template_id', 'template_section_id', 'key', 'label', 'field_type',
        'placeholder', 'helper_text', 'options', 'default_value', 'validation_rules',
        'is_required', 'is_filterable', 'show_in_table', 'show_in_pdf', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'field_type' => FieldType::class,
            'options' => 'array',
            'default_value' => 'array',
            'validation_rules' => 'array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'show_in_table' => 'boolean',
            'show_in_pdf' => 'boolean',
        ];
    }

    /**
     * Mengembalikan opsi pilihan dalam format pasangan [value => label].
     *
     * @return array<string, string>
     */
    public function optionPairs(): array
    {
        $pairs = [];

        foreach ($this->options ?? [] as $key => $item) {
            if (is_array($item)) {
                $value = (string) ($item['value'] ?? $key);
                $label = (string) ($item['label'] ?? $item['value'] ?? $value);
                $pairs[$value] = $label;
            } elseif (is_string($key)) {
                $pairs[$key] = (string) $item;
            } else {
                $pairs[(string) $item] = (string) $item;
            }
        }

        return $pairs;
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(TemplateSection::class, 'template_section_id');
    }
}

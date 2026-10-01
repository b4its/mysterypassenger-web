<?php

namespace App\Models;

use App\Enums\OutputSection;
use Database\Factories\QuestionGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionGroup extends Model
{
    /** @use HasFactory<QuestionGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'form_template_id', 'parent_id', 'name', 'description',
        'output_section', 'weight', 'depth', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'output_section' => OutputSection::class,
            'weight' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    /** Semua turunan rekursif (dipakai saat scoring & PDF). */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    protected static function booted(): void
    {
        static::saving(function (self $group) {
            $group->depth = $group->parent_id
                ? (self::find($group->parent_id)?->depth ?? 0) + 1
                : 0;
        });
    }
}

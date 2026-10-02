<?php

namespace App\Models;

use App\Enums\ScoringStrategy;
use App\Enums\TemplateStatus;
use Database\Factories\FormTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormTemplate extends Model
{
    /** @use HasFactory<FormTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transport_mode_id', 'name', 'slug', 'version', 'description', 'status',
        'scoring_enabled', 'scoring_strategy', 'passing_score',
        'max_evidence_per_answer', 'locale', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => TemplateStatus::class,
            'scoring_strategy' => ScoringStrategy::class,
            'scoring_enabled' => 'boolean',
            'passing_score' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TemplateSection::class)->orderBy('sort_order');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(TemplateField::class)->orderBy('sort_order');
    }

    public function questionGroups(): HasMany
    {
        return $this->hasMany(QuestionGroup::class)->orderBy('sort_order');
    }

    public function rootGroups(): HasMany
    {
        return $this->questionGroups()->whereNull('parent_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TemplateAssignment::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'template_assignments')
            ->withPivot(['assigned_by', 'starts_at', 'due_at', 'notes'])
            ->withTimestamps();
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public function isEditable(): bool
    {
        return $this->status === TemplateStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === TemplateStatus::Published;
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', TemplateStatus::Published);
    }
}

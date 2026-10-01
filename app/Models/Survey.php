<?php

namespace App\Models;

use App\Enums\SurveyStatus;
use App\Enums\UserRole;
use App\Observers\SurveyObserver;
use App\Services\SurveyCodeGenerator;
use Database\Factories\SurveyFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[ObservedBy(SurveyObserver::class)]
class Survey extends Model
{
    /** @use HasFactory<SurveyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'idempotency_key', 'code', 'transport_mode_id', 'form_template_id',
        'template_version', 'user_id', 'evaluator_name', 'executed_at', 'location_text',
        'latitude', 'longitude', 'status', 'total_score', 'max_score',
        'score_percentage', 'is_passed', 'summary_note', 'submitted_at',
        'reviewed_by', 'reviewed_at', 'review_note', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => SurveyStatus::class,
            'executed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'meta' => 'array',
            'is_passed' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'total_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'score_percentage' => 'decimal:2',
        ];
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class);
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(SurveyFieldValue::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    public function media(): HasManyThrough
    {
        return $this->hasManyThrough(SurveyAnswerMedia::class, SurveyAnswer::class);
    }

    /** Nilai field profil berdasarkan key — dipakai di kop PDF dan kolom tabel. */
    public function field(string $key): mixed
    {
        return $this->fieldValues->firstWhere('field_key', $key)?->value;
    }

    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        return $user->role === UserRole::Surveyor
            ? $q->where('user_id', $user->id)
            : $q;
    }

    protected static function booted(): void
    {
        static::creating(function (self $survey) {
            $survey->uuid ??= (string) Str::ulid();
            $survey->code ??= app(SurveyCodeGenerator::class)->generate($survey);
        });
    }
}

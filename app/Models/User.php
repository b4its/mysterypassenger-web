<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role',
        'phone', 'organization', 'is_active', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Panel Filament khusus ADMIN. Reviewer & surveyor memakai halaman web
        // non-Filament (lihat routes/web.php, prefix /app).
        return $this->is_active && $this->isAdmin();
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public function reviewedSurveys(): HasMany
    {
        return $this->hasMany(Survey::class, 'reviewed_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TemplateAssignment::class);
    }

    public function assignedTemplates(): BelongsToMany
    {
        return $this->belongsToMany(FormTemplate::class, 'template_assignments')->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isReviewer(): bool
    {
        return $this->role === UserRole::Reviewer;
    }

    public function isSurveyor(): bool
    {
        return $this->role === UserRole::Surveyor;
    }
}

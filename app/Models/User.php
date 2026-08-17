<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\EmailNormalizer;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /** @return HasMany<Company, $this> */
    public function assignedCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'assigned_user_id');
    }

    /** @return HasMany<Opportunity, $this> */
    public function assignedOpportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'assigned_user_id');
    }

    /** @return HasMany<Task, $this> */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_user_id');
    }

    /** @return HasMany<Task, $this> */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by_user_id');
    }

    /** @return HasMany<Activity, $this> */
    public function assignedActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'assigned_user_id');
    }

    /** @return HasMany<Activity, $this> */
    public function createdActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'created_by_user_id');
    }

    /** @return HasMany<DataImport, $this> */
    public function dataImports(): HasMany
    {
        return $this->hasMany(DataImport::class, 'user_id');
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains(fn (Role $role): bool => $role->active && $role->slug === $slug);
    }

    public function hasPermission(string $slug): bool
    {
        return $this->roles()
            ->where('roles.active', true)
            ->whereHas('permissions', fn ($query) => $query->where('permissions.slug', $slug))
            ->exists();
    }

    public function primaryRole(): ?Role
    {
        return $this->roles->firstWhere('active', true);
    }

    /** @return Attribute<string, string> */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $email): string => EmailNormalizer::normalize($email),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

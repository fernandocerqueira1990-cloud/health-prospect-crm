<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description', 'active'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const ADMIN_SLUG = 'admin';

    protected static function booted(): void
    {
        static::updating(function (Role $role): void {
            $originalSlug = (string) $role->getRawOriginal('slug');

            if ($originalSlug === self::ADMIN_SLUG
                && ($role->slug !== self::ADMIN_SLUG || ! $role->active)) {
                throw new DomainException('A identidade e o estado ativo da role Administrador são protegidos.');
            }

            if ($originalSlug !== self::ADMIN_SLUG && $role->slug === self::ADMIN_SLUG) {
                throw new DomainException('O slug admin é reservado para a role Administrador.');
            }
        });
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}

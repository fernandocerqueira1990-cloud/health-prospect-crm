<?php

namespace App\Models;

use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description'])]
class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory;

    public const SLUGS = [
        'dashboard.view',
        'users.view', 'users.create', 'users.update', 'users.manage_roles',
        'roles.view', 'roles.create', 'roles.update', 'roles.manage_permissions',
        'companies.view', 'companies.create', 'companies.update', 'companies.delete',
        'contacts.view', 'contacts.create', 'contacts.update', 'contacts.delete',
        'leads.view', 'leads.create', 'leads.update', 'leads.delete',
        'opportunities.view', 'opportunities.create', 'opportunities.update', 'opportunities.delete',
        'activities.view', 'activities.create', 'activities.update',
        'tasks.view', 'tasks.create', 'tasks.update',
        'campaigns.view', 'campaigns.create', 'campaigns.update',
        'reports.view', 'imports.execute', 'settings.view', 'settings.manage', 'audit.view',
    ];

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}

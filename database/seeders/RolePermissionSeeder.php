<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    private const DEFAULTS = [
        'sales_manager' => ['dashboard.view', 'companies.view', 'companies.create', 'companies.update', 'contacts.view', 'contacts.create', 'contacts.update', 'leads.view', 'leads.create', 'leads.update', 'opportunities.view', 'opportunities.create', 'opportunities.update', 'activities.view', 'activities.create', 'activities.update', 'activities.delete', 'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete', 'imports.view', 'imports.create', 'imports.update', 'imports.delete', 'reports.view'],
        'supervisor' => ['dashboard.view', 'companies.view', 'contacts.view', 'leads.view', 'leads.create', 'leads.update', 'opportunities.view', 'opportunities.create', 'opportunities.update', 'activities.view', 'activities.create', 'activities.update', 'tasks.view', 'tasks.create', 'tasks.update', 'imports.view', 'imports.create', 'imports.update', 'reports.view'],
        'sales_rep' => ['dashboard.view', 'companies.view', 'contacts.view', 'leads.view', 'leads.create', 'leads.update', 'opportunities.view', 'opportunities.create', 'opportunities.update', 'activities.view', 'activities.create', 'activities.update', 'tasks.view', 'tasks.create', 'tasks.update'],
        'marketing' => ['dashboard.view', 'companies.view', 'contacts.view', 'leads.view', 'campaigns.view', 'campaigns.create', 'campaigns.update', 'imports.view', 'imports.create', 'imports.update', 'reports.view'],
        'analyst' => ['dashboard.view', 'companies.view', 'contacts.view', 'leads.view', 'opportunities.view', 'activities.view', 'tasks.view', 'campaigns.view', 'imports.view', 'reports.view'],
        'readonly' => ['dashboard.view', 'companies.view', 'contacts.view', 'leads.view', 'opportunities.view', 'activities.view', 'tasks.view', 'campaigns.view', 'imports.view', 'reports.view'],
    ];

    public function run(): void
    {
        $permissions = Permission::query()->pluck('id', 'slug');
        Role::where('slug', 'admin')->firstOrFail()->permissions()->sync($permissions->values()->all());

        foreach (self::DEFAULTS as $roleSlug => $slugs) {
            Role::where('slug', $roleSlug)->firstOrFail()->permissions()->sync($permissions->only($slugs)->values()->all());
        }
    }
}

<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ContactPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Support\Testing\TestingDatabaseGuard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(TestingDatabaseGuard::class)->ensureSafe(
            app()->environment(),
            (string) config('database.connections.'.config('database.default').'.database'),
        );

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Contact::class, ContactPolicy::class);

        Gate::before(function (User $user): ?bool {
            return $user->hasRole('admin') ? true : null;
        });

        foreach (Permission::SLUGS as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }
    }
}

<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Contact;
use App\Models\DataImport;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ContactPolicy;
use App\Policies\ImportPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Support\EmailNormalizer;
use App\Support\Testing\TestingDatabaseGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('register', function (Request $request): array {
            $email = EmailNormalizer::normalize((string) $request->input('email'));

            return [
                Limit::perMinute((int) config('security.rate_limits.register.identity_attempts'))
                    ->by('register:identity:'.hash('sha256', $email)),
                Limit::perMinute((int) config('security.rate_limits.register.ip_attempts'))
                    ->by('register:ip:'.hash('sha256', (string) $request->ip())),
            ];
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Contact::class, ContactPolicy::class);
        Gate::policy(DataImport::class, ImportPolicy::class);

        Gate::before(function (User $user): ?bool {
            if (! $user->active
                || ($user->hasRole(Role::TESTER_SLUG) && ! (bool) config('features.tester_access'))) {
                return false;
            }

            return $user->hasRole('admin') ? true : null;
        });

        foreach (Permission::SLUGS as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }
    }
}

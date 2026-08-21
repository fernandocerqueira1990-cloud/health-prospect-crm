<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\AuthenticationRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class AuthenticationRbacAuditHardeningTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
        RateLimiter::clear('unused');
    }

    public function test_login_regenerates_the_session_identifier(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Valid-password-123')]);
        $this->startSession();
        $previousId = session()->getId();

        $this->post('/login', ['email' => $user->email, 'password' => 'Valid-password-123'])
            ->assertRedirect(route('dashboard'));

        $this->assertNotSame($previousId, session()->getId());
    }

    public function test_logout_invalidates_session_data_and_rotates_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['sensitive_marker' => 'must-disappear']);
        $previousToken = session()->token();

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertFalse(session()->has('sensitive_marker'));
        $this->assertNotSame($previousToken, session()->token());
    }

    public function test_invalid_credentials_use_the_same_message_for_known_and_unknown_accounts(): void
    {
        $user = User::factory()->create();
        $message = 'As credenciais informadas são inválidas ou o usuário está inativo.';
        $known = $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        $unknown = $this->from('/login')->post('/login', ['email' => 'missing@example.test', 'password' => 'wrong']);

        $known->assertSessionHasErrors(['email' => $message]);
        $unknown->assertSessionHasErrors(['email' => $message]);
    }

    public function test_login_is_limited_by_identity_across_source_ips_and_resets_after_window(): void
    {
        config()->set('security.rate_limits.login.identity_attempts', 2);
        config()->set('security.rate_limits.login.ip_attempts', 20);
        config()->set('security.rate_limits.login.decay_seconds', 1);
        $user = User::factory()->create();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11'])->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.12'])->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(429);

        $this->travel(2)->seconds();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.12'])->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
    }

    public function test_login_is_limited_by_ip_across_different_identities(): void
    {
        config()->set('security.rate_limits.login.identity_attempts', 20);
        config()->set('security.rate_limits.login.ip_attempts', 2);

        foreach (['one@example.test', 'two@example.test'] as $email) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])->post('/login', ['email' => $email, 'password' => 'wrong']);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])->post('/login', ['email' => 'three@example.test', 'password' => 'wrong'])
            ->assertStatus(429);
        $this->assertDatabaseHas('audit_logs', ['action' => 'login_blocked']);
    }

    public function test_retry_after_uses_only_the_blocking_identity_counter(): void
    {
        $this->freezeTime();
        config()->set('security.rate_limits.login.identity_attempts', 2);
        config()->set('security.rate_limits.login.ip_attempts', 3);
        $limiter = app(AuthenticationRateLimiter::class);
        $request = $this->loginRequest('identity-only@example.test', '192.0.2.41');

        RateLimiter::hit($limiter->loginIdentityKey($request), 10);
        RateLimiter::hit($limiter->loginIdentityKey($request), 10);
        RateLimiter::hit($limiter->loginIpKey($request), 40);

        $this->assertSame(10, $limiter->loginAvailableIn($request));
    }

    public function test_retry_after_uses_only_the_blocking_ip_counter(): void
    {
        $this->freezeTime();
        config()->set('security.rate_limits.login.identity_attempts', 3);
        config()->set('security.rate_limits.login.ip_attempts', 2);
        $limiter = app(AuthenticationRateLimiter::class);
        $request = $this->loginRequest('ip-only@example.test', '192.0.2.42');

        RateLimiter::hit($limiter->loginIdentityKey($request), 40);
        RateLimiter::hit($limiter->loginIpKey($request), 10);
        RateLimiter::hit($limiter->loginIpKey($request), 10);

        $this->assertSame(10, $limiter->loginAvailableIn($request));
    }

    public function test_retry_after_uses_the_longest_window_when_both_counters_block(): void
    {
        $this->freezeTime();
        config()->set('security.rate_limits.login.identity_attempts', 2);
        config()->set('security.rate_limits.login.ip_attempts', 2);
        $limiter = app(AuthenticationRateLimiter::class);
        $request = $this->loginRequest('both@example.test', '192.0.2.43');

        foreach ([[$limiter->loginIdentityKey($request), 10], [$limiter->loginIpKey($request), 20]] as [$key, $decay]) {
            RateLimiter::hit($key, $decay);
            RateLimiter::hit($key, $decay);
        }

        $this->assertSame(20, $limiter->loginAvailableIn($request));
        $this->travel(11)->seconds();
        $this->assertSame(9, $limiter->loginAvailableIn($request));
        $this->travel(10)->seconds();
        $this->assertFalse($limiter->tooManyLoginAttempts($request));
        $this->assertSame(0, $limiter->loginAvailableIn($request));
    }

    public function test_public_registration_is_rate_limited_by_ip_when_enabled(): void
    {
        config()->set('features.public_registration', true);
        config()->set('security.rate_limits.register.identity_attempts', 20);
        config()->set('security.rate_limits.register.ip_attempts', 2);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.30'])
                ->post('/register', [])
                ->assertSessionHasErrors(['name', 'email', 'password']);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.30'])
            ->post('/register', [])
            ->assertStatus(429);
    }

    public function test_password_recovery_endpoints_are_not_publicly_exposed(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'person@example.test'])->assertNotFound();
        $this->get('/reset-password/arbitrary-token')->assertNotFound();
        $this->post('/reset-password', [])->assertNotFound();
    }

    public function test_delegated_user_manager_cannot_assign_the_administrator_role_or_modify_an_admin(): void
    {
        $manager = $this->userWithPermissions(['users.create', 'users.update', 'users.manage_roles']);
        $administrator = $this->admin();
        $adminRole = Role::where('slug', Role::ADMIN_SLUG)->firstOrFail();

        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Escalation', 'email' => 'escalation@example.test', 'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123', 'active' => true, 'role_ids' => [$adminRole->id],
        ])->assertForbidden();

        $this->actingAs($manager)->get(route('admin.users.edit', $administrator))->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'escalation@example.test']);
    }

    public function test_delegated_user_manager_cannot_assign_an_alternative_administrative_role(): void
    {
        $manager = $this->userWithPermissions(['users.create', 'users.update', 'users.manage_roles']);
        $administrativeRole = Role::factory()->create();
        $administrativeRole->permissions()->attach(Permission::where('slug', 'audit.view')->firstOrFail());

        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Escalation', 'email' => 'alternative-escalation@example.test', 'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123', 'active' => true, 'role_ids' => [$administrativeRole->id],
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'alternative-escalation@example.test']);
    }

    public function test_tester_role_cannot_be_combined_with_an_administrative_role(): void
    {
        $administrator = $this->admin();
        $target = User::factory()->create();
        $roles = Role::whereIn('slug', [Role::ADMIN_SLUG, Role::TESTER_SLUG])->pluck('id')->all();

        $this->actingAs($administrator)->put(route('admin.users.update', $target), [
            'name' => $target->name, 'email' => $target->email, 'active' => true, 'role_ids' => $roles,
        ])->assertSessionHasErrors('role_ids');

        $this->assertTrue($target->refresh()->roles->isEmpty());
    }

    public function test_delegated_role_manager_cannot_grant_administrative_permissions(): void
    {
        $manager = $this->userWithPermissions(['roles.create', 'roles.update', 'roles.manage_permissions']);
        $privileged = Permission::where('slug', 'users.manage_roles')->firstOrFail();

        $this->actingAs($manager)->post(route('admin.roles.store'), [
            'name' => 'Escalated', 'slug' => 'escalated', 'active' => true,
            'permission_ids' => [$privileged->id],
        ])->assertForbidden();
    }

    public function test_administrator_cannot_deactivate_or_demote_its_own_current_session(): void
    {
        $administrator = $this->admin();

        $this->actingAs($administrator)->put(route('admin.users.update', $administrator), [
            'name' => $administrator->name, 'email' => $administrator->email,
            'active' => false, 'role_ids' => [],
        ])->assertSessionHasErrors(['active', 'role_ids']);

        $this->assertTrue($administrator->refresh()->active);
        $this->assertTrue($administrator->hasRole(Role::ADMIN_SLUG));
    }

    public function test_audit_sanitization_removes_nested_header_cookie_session_and_secret_variants(): void
    {
        app(AuditService::class)->record('security_hardening_test', after: [
            'safe' => 'retained',
            'nested' => [
                'Authorization' => 'Bearer forbidden',
                'x-csrf-token' => 'csrf-forbidden',
                'session-id' => 'session-forbidden',
                'credentials' => ['client_secret' => 'secret-forbidden', 'api_token' => 'token-forbidden'],
            ],
        ]);

        $serialized = json_encode(AuditLog::where('action', 'security_hardening_test')->firstOrFail()->after);
        $this->assertStringContainsString('retained', $serialized);
        foreach (['forbidden', 'csrf-forbidden', 'session-forbidden', 'secret-forbidden', 'token-forbidden'] as $secret) {
            $this->assertStringNotContainsString($secret, $serialized);
        }
    }

    /** @param list<string> $slugs */
    private function userWithPermissions(array $slugs): User
    {
        $role = Role::factory()->create();
        $role->permissions()->sync(Permission::whereIn('slug', $slugs)->pluck('id'));
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    private function loginRequest(string $email, string $ip): Request
    {
        return Request::create('/login', 'POST', ['email' => $email], server: ['REMOTE_ADDR' => $ip]);
    }
}

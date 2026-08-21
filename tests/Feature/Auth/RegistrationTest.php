<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_PASSWORD = 'Strong-password-123';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('features.public_registration', true);
        config()->set('features.tester_access', true);

        $this->seed(DatabaseSeeder::class);
        RateLimiter::clear(md5('registertester@example.com|127.0.0.1'));
    }

    public function test_registration_page_is_available_to_guests(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Crie sua conta')
            ->assertSee('Já possui uma conta? Entrar');
    }

    public function test_authenticated_user_cannot_access_registration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('register'))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->post(route('register.store'), $this->validPayload())
            ->assertRedirect(route('dashboard'));
    }

    public function test_registration_creates_active_hashed_tester_authenticates_and_redirects(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload());

        $response->assertRedirect(route('dashboard'));
        $user = User::where('email', 'tester@example.com')->firstOrFail();
        $this->assertTrue($user->active);
        $this->assertTrue(Hash::check(self::VALID_PASSWORD, $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertSame([Role::TESTER_SLUG], $user->roles()->pluck('slug')->all());
    }

    public function test_administrative_fields_in_request_are_ignored(): void
    {
        $admin = Role::where('slug', Role::ADMIN_SLUG)->firstOrFail();
        $payload = $this->validPayload() + [
            'active' => false,
            'role_id' => $admin->id,
            'role' => Role::ADMIN_SLUG,
            'role_ids' => [$admin->id],
            'permissions' => Permission::pluck('id')->all(),
        ];

        $this->post(route('register.store'), $payload)->assertRedirect(route('dashboard'));

        $user = User::where('email', 'tester@example.com')->firstOrFail();
        $this->assertTrue($user->active);
        $this->assertSame([Role::TESTER_SLUG], $user->roles()->pluck('slug')->all());
    }

    public function test_duplicate_email_is_rejected_after_normalization(): void
    {
        User::factory()->create(['email' => 'tester@example.com']);

        $this->from(route('register'))->post(route('register.store'), [
            ...$this->validPayload(),
            'email' => ' TESTER@EXAMPLE.COM ',
        ])->assertRedirect(route('register'))->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'tester@example.com')->count());
    }

    public function test_invalid_password_is_rejected(): void
    {
        $this->post(route('register.store'), [
            ...$this->validPayload(),
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'tester@example.com']);
    }

    public function test_csrf_protection_remains_enabled(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="_token"', false);

        $route = Route::getRoutes()->getByName('register.store');
        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());
    }

    public function test_registration_is_audited_without_password_or_request_privilege_fields(): void
    {
        $this->post(route('register.store'), $this->validPayload() + ['role' => 'admin']);

        $user = User::where('email', 'tester@example.com')->firstOrFail();
        $log = AuditLog::where('action', 'user_registered')->firstOrFail();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame(['name', 'email', 'active'], array_keys($log->after));
        $this->assertArrayNotHasKey('password', $log->after);
        $this->assertArrayNotHasKey('role', $log->after);
    }

    public function test_tester_has_all_and_only_operational_permissions(): void
    {
        $expected = [
            'dashboard.view',
            'companies.view', 'companies.create', 'companies.update', 'companies.delete',
            'contacts.view', 'contacts.create', 'contacts.update', 'contacts.delete',
            'leads.view', 'leads.create', 'leads.update', 'leads.delete',
            'opportunities.view', 'opportunities.create', 'opportunities.update', 'opportunities.delete',
            'activities.view', 'activities.create', 'activities.update', 'activities.delete',
            'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete',
            'campaigns.view', 'campaigns.create', 'campaigns.update', 'campaigns.delete',
            'reports.view',
            'imports.view', 'imports.create', 'imports.update', 'imports.delete',
        ];
        $actual = Role::where('slug', Role::TESTER_SLUG)->firstOrFail()
            ->permissions()->pluck('slug')->all();

        $this->assertEqualsCanonicalizing($expected, $actual);
        $this->assertEmpty(array_filter(
            $actual,
            fn (string $slug): bool => str_starts_with($slug, 'users.')
                || str_starts_with($slug, 'roles.')
                || str_starts_with($slug, 'settings.')
                || str_starts_with($slug, 'audit.'),
        ));
    }

    public function test_role_seeders_are_idempotent(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(1, Role::where('slug', Role::TESTER_SLUG)->count());
        $tester = Role::where('slug', Role::TESTER_SLUG)->firstOrFail();
        $this->assertSame($tester->permissions()->count(), $tester->permissions()->distinct()->count());
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('register.store'), [
                ...$this->validPayload(),
                'password_confirmation' => 'does-not-match',
            ]);
        }

        $this->post(route('register.store'), $this->validPayload())->assertTooManyRequests();
        $this->assertDatabaseMissing('users', ['email' => 'tester@example.com']);
    }

    public function test_registration_is_not_available_when_public_registration_is_disabled(): void
    {
        config()->set('features.public_registration', false);

        $this->get('/register')->assertNotFound();

        $this->post('/register', $this->validPayload())->assertNotFound();

        $this->assertDatabaseMissing('users', [
            'email' => 'tester@example.com',
        ]);
    }

    public function test_login_hides_registration_link_when_public_registration_is_disabled(): void
    {
        config()->set('features.public_registration', false);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Criar uma conta para testar')
            ->assertDontSee('Ainda não tem uma conta?');
    }

    /** @return array<string, string> */
    private function validPayload(): array
    {
        return [
            'name' => 'Pessoa Testadora',
            'email' => 'tester@example.com',
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
        ];
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_rendered(): void
    {
        $this->get('/login')->assertOk()->assertSee('Acesse sua conta');
    }

    public function test_user_can_login_with_valid_credentials_and_last_login_is_updated(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Valid-password-123')]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'Valid-password-123', 'remember' => true]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'login_success', 'user_id' => $user->id]);
    }

    public function test_invalid_password_is_rejected_and_audited(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'login_failed', 'user_id' => $user->id]);
    }

    public function test_unknown_user_is_rejected_without_recording_the_email(): void
    {
        $this->post('/login', ['email' => 'unknown@example.com', 'password' => 'wrong-password'])->assertSessionHasErrors('email');

        $log = AuditLog::where('action', 'login_failed')->firstOrFail();
        $this->assertNull($log->user_id);
        $this->assertNull($log->before);
        $this->assertNull($log->after);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout_and_event_is_audited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'logout', 'user_id' => $user->id]);
    }

    public function test_guest_is_redirected_from_protected_route(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'invalid']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'invalid'])
            ->assertSessionHasErrors('email');
    }

    public function test_login_normalizes_email_case_and_surrounding_spaces(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post('/login', [
            'email' => '  UsEr@ExAmPlE.CoM  ',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}

<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class TransportSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_responses_contain_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $policy = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
    }

    public function test_hsts_is_absent_for_local_http_requests(): void
    {
        config()->set('security.headers.hsts.enabled', true);

        $this->get('http://localhost/login')
            ->assertOk()
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_trusted_proxy_recognizes_forwarded_https_and_adds_hsts(): void
    {
        config()->set('security.headers.hsts.enabled', true);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeader('X-Forwarded-Proto', 'https')
            ->get('http://crm.example.test/login')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_untrusted_client_cannot_force_https_with_forwarded_headers(): void
    {
        config()->set('security.headers.hsts.enabled', true);
        Route::middleware('web')->get('/_security/request', fn (Request $request): array => [
            'secure' => $request->isSecure(),
            'host' => $request->getHost(),
            'port' => $request->getPort(),
            'client_ip' => $request->ip(),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders([
                'X-Forwarded-For' => '127.0.0.1',
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'spoofed.example.test',
                'X-Forwarded-Port' => '443',
            ])->get('http://crm.example.test/_security/request');

        $response->assertOk()
            ->assertHeaderMissing('Strict-Transport-Security')
            ->assertJson([
                'secure' => false,
                'host' => 'crm.example.test',
                'port' => 80,
                'client_ip' => '203.0.113.10',
            ]);
    }

    public function test_session_secure_cookie_is_configurable_without_breaking_local_login(): void
    {
        config()->set('session.secure', false);

        $user = User::factory()->create();

        $this->post('http://localhost/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        config()->set('session.secure', true);

        $response = $this->get('https://localhost/dashboard');
        $sessionCookie = collect($response->headers->getCookies())
            ->first(fn (Cookie $cookie): bool => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($sessionCookie);
        $this->assertTrue($sessionCookie->isSecure());
        $this->assertTrue($sessionCookie->isHttpOnly());
        $this->assertSame('lax', $sessionCookie->getSameSite());
    }
}

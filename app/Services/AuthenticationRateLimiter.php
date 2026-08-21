<?php

namespace App\Services;

use App\Support\EmailNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticationRateLimiter
{
    public function tooManyLoginAttempts(Request $request): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->loginIdentityKey($request),
            (int) config('security.rate_limits.login.identity_attempts'),
        ) || RateLimiter::tooManyAttempts(
            $this->loginIpKey($request),
            (int) config('security.rate_limits.login.ip_attempts'),
        );
    }

    public function hitLogin(Request $request): void
    {
        $decay = (int) config('security.rate_limits.login.decay_seconds');

        RateLimiter::hit($this->loginIdentityKey($request), $decay);
        RateLimiter::hit($this->loginIpKey($request), $decay);
    }

    public function clearLoginIdentity(Request $request): void
    {
        RateLimiter::clear($this->loginIdentityKey($request));
    }

    public function loginAvailableIn(Request $request): int
    {
        $blockingWindows = [];
        $identityKey = $this->loginIdentityKey($request);
        $ipKey = $this->loginIpKey($request);

        if (RateLimiter::tooManyAttempts($identityKey, (int) config('security.rate_limits.login.identity_attempts'))) {
            $blockingWindows[] = RateLimiter::availableIn($identityKey);
        }

        if (RateLimiter::tooManyAttempts($ipKey, (int) config('security.rate_limits.login.ip_attempts'))) {
            $blockingWindows[] = RateLimiter::availableIn($ipKey);
        }

        return $blockingWindows === [] ? 0 : max($blockingWindows);
    }

    public function loginIdentityKey(Request $request): string
    {
        $email = EmailNormalizer::normalize((string) $request->input('email'));

        return 'auth:login:identity:'.hash('sha256', $email);
    }

    public function loginIpKey(Request $request): string
    {
        return 'auth:login:ip:'.hash('sha256', (string) $request->ip());
    }
}

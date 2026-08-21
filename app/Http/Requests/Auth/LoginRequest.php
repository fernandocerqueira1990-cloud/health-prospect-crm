<?php

namespace App\Http\Requests\Auth;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\AuthenticationRateLimiter;
use App\Support\EmailNormalizer;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => EmailNormalizer::normalize((string) $this->input('email'))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function authenticate(AuditService $audit, AuthenticationRateLimiter $limiter): User
    {
        $this->ensureIsNotRateLimited($audit, $limiter);
        $email = EmailNormalizer::normalize($this->string('email')->toString());
        $credentials = ['email' => $email, 'password' => $this->string('password')->toString(), 'active' => true];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            $limiter->hitLogin($this);
            $candidate = User::query()->where('email', $email)->where('active', true)->first();
            $audit->record('login_failed', user: $candidate, request: $this);

            throw ValidationException::withMessages([
                'email' => __('As credenciais informadas são inválidas ou o usuário está inativo.'),
            ]);
        }

        $limiter->clearLoginIdentity($this);

        /** @var User $user */
        $user = Auth::user();

        if (
            $user->hasRole(Role::TESTER_SLUG)
            && ! (bool) config('features.tester_access')
        ) {
            Auth::guard('web')->logout();

            $this->session()->invalidate();
            $this->session()->regenerateToken();

            $audit->record(
                'login_failed',
                user: $user,
                request: $this,
            );

            throw ValidationException::withMessages([
                'email' => __('As credenciais informadas são inválidas ou o usuário está inativo.'),
            ]);
        }

        return $user;
    }

    public function ensureIsNotRateLimited(AuditService $audit, AuthenticationRateLimiter $limiter): void
    {
        if (! $limiter->tooManyLoginAttempts($this)) {
            return;
        }

        event(new Lockout($this));
        $seconds = $limiter->loginAvailableIn($this);
        $audit->record('login_blocked', after: ['retry_after_seconds' => $seconds], request: $this);

        throw new HttpResponseException(response(
            __('Muitas tentativas. Tente novamente em :seconds segundos.', ['seconds' => $seconds]),
            429,
            ['Retry-After' => (string) $seconds],
        ));
    }
}

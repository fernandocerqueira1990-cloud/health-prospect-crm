<?php

namespace App\Http\Requests\Auth;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Support\EmailNormalizer;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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

    public function authenticate(AuditService $audit): User
    {
        $this->ensureIsNotRateLimited();
        $email = EmailNormalizer::normalize($this->string('email')->toString());
        $credentials = ['email' => $email, 'password' => $this->string('password')->toString(), 'active' => true];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 60);
            $candidate = User::where('email', $email)->first();
            $audit->record('login_failed', user: $candidate?->active ? $candidate : null, request: $this);

            throw ValidationException::withMessages([
                'email' => __('As credenciais informadas são inválidas ou o usuário está inativo.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

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

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('Muitas tentativas. Tente novamente em :seconds segundos.', ['seconds' => $seconds]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(EmailNormalizer::normalize($this->string('email')->toString()).'|'.$this->ip());
    }
}

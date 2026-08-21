<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function __construct(private readonly AuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->active) {
            $this->audit->record('session_access_blocked', $request->user(), after: ['reason' => 'account_unavailable'], user: $request->user(), request: $request);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => __('Não foi possível acessar esta conta.'),
                ]);
        }

        if (
            $request->user()
            && $request->user()->hasRole(Role::TESTER_SLUG)
            && ! (bool) config('features.tester_access')
        ) {
            $this->audit->record('session_access_blocked', $request->user(), after: ['reason' => 'account_unavailable'], user: $request->user(), request: $request);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => __('Não foi possível acessar esta conta.'),
                ]);
        }

        return $next($request);
    }
}

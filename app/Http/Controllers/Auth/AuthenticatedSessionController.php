<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditService $audit): RedirectResponse
    {
        $user = $request->authenticate($audit);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->record('login_success', $user, after: ['last_login_at' => $user->last_login_at], user: $user, request: $request);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditService $audit): RedirectResponse
    {
        $user = $request->user();
        $audit->record('logout', $user, user: $user, request: $request);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

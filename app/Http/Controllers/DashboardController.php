<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('dashboard.view');

        /** @var User $user */
        $user = request()->user();

        $canViewCompanies = $user->can('viewAny', Company::class);
        $canViewContacts = $user->can('viewAny', Contact::class);

        $stats = [
            'companies' => $canViewCompanies ? Company::query()->count() : null,
            'high_priority_companies' => $canViewCompanies
                ? Company::query()->whereIn('priority', ['high', 'critical'])->count()
                : null,
            'active_contacts' => $canViewContacts ? Contact::query()->where('active', true)->count() : null,
            'decision_contacts' => $canViewContacts
                ? Contact::query()
                    ->where('active', true)
                    ->whereIn('decision_role', ['decision_maker', 'champion'])
                    ->count()
                : null,
        ];

        $recentCompanies = $canViewCompanies
            ? Company::query()->with('assignedUser')->latest()->limit(5)->get()
            : collect();

        $recentContacts = $canViewContacts
            ? Contact::query()->with('company')->latest()->limit(5)->get()
            : collect();

        return view('dashboard', compact(
            'stats',
            'recentCompanies',
            'recentContacts',
            'canViewCompanies',
            'canViewContacts',
        ));
    }
}

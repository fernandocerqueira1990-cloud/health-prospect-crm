<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Task;
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
        $canViewTasks = $user->can('viewAny', Task::class);

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

        $commercialQueue = $canViewTasks
            ? $this->commercialQueue($user)
            : null;

        return view('dashboard', compact(
            'stats',
            'recentCompanies',
            'recentContacts',
            'commercialQueue',
            'canViewCompanies',
            'canViewContacts',
            'canViewTasks',
        ));
    }

    /** @return array{overdue: int, today: int, upcoming: int, next_tasks: \Illuminate\Database\Eloquent\Collection<int, Task>} */
    private function commercialQueue(User $user): array
    {
        $baseQuery = Task::query()
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_at');

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return [
            'overdue' => (clone $baseQuery)
                ->where('due_at', '<', $todayStart)
                ->count(),
            'today' => (clone $baseQuery)
                ->whereBetween('due_at', [$todayStart, $todayEnd])
                ->count(),
            'upcoming' => (clone $baseQuery)
                ->where('due_at', '>', $todayEnd)
                ->count(),
            'next_tasks' => (clone $baseQuery)
                ->with([
                    'lead:id,name,company_name',
                    'opportunity:id,title',
                ])
                ->orderBy('due_at')
                ->limit(5)
                ->get(),
        ];
    }
}

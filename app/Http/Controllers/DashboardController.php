<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
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
        $canViewLeads = $user->can('viewAny', Lead::class);
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

        $commercialQueue = ($canViewTasks || $canViewLeads)
            ? $this->commercialQueue($user, $canViewTasks, $canViewLeads)
            : null;

        return view('dashboard', compact(
            'stats',
            'recentCompanies',
            'recentContacts',
            'commercialQueue',
            'canViewCompanies',
            'canViewContacts',
            'canViewLeads',
            'canViewTasks',
        ));
    }

    /** @return array{overdue: int|null, today: int|null, upcoming: int|null, inactive_leads: int|null, next_tasks: \Illuminate\Database\Eloquent\Collection<int, Task>} */
    private function commercialQueue(
        User $user,
        bool $canViewTasks,
        bool $canViewLeads,
    ): array {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $baseQuery = Task::query()
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_at');

        $cutoff = now()->subDays(
            max(1, (int) config('commercial.lead_inactivity_days', 7)),
        );

        $inactiveLeads = $canViewLeads
            ? Lead::query()
                ->where('assigned_user_id', $user->id)
                ->whereNotIn('status', ['converted', 'disqualified'])
                ->where('created_at', '<=', $cutoff)
                ->where(function ($query) use ($cutoff): void {
                    $query->whereNull('last_interaction_at')
                        ->orWhere('last_interaction_at', '<=', $cutoff);
                })
                ->count()
            : null;

        if (! $canViewTasks) {
            return [
                'overdue' => null,
                'today' => null,
                'upcoming' => null,
                'inactive_leads' => $inactiveLeads,
                'next_tasks' => Task::query()->whereRaw('1 = 0')->get(),
            ];
        }

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
            'inactive_leads' => $inactiveLeads,
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

<?php

namespace App\Http\Controllers;

use App\Actions\Activities\CreateActivityAction;
use App\Actions\Activities\DeleteActivityAction;
use App\Actions\Activities\UpdateActivityAction;
use App\Http\Requests\Activities\ActivityIndexRequest;
use App\Http\Requests\Activities\StoreActivityRequest;
use App\Http\Requests\Activities\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Queries\ActivityIndexQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(
        ActivityIndexRequest $request,
        ActivityIndexQuery $query,
    ): View {
        return view('activities.index', [
            'activities' => $query->paginate($request->validated()),
            'assignedUsers' => $this->users(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Activity::class);

        return view('activities.create', [
            'assignedUsers' => $this->users(),
            'companies' => $this->companies(),
            'contacts' => $this->contacts(),
            'leads' => $this->leads(),
            'opportunities' => $this->opportunities(),
        ]);
    }

    public function store(
        StoreActivityRequest $request,
        CreateActivityAction $action,
    ): RedirectResponse {
        $activity = $action->execute(
            $request->validated(),
            $request->user(),
        );

        return $this->authorizedRedirect(
            $request->user(),
            'activities.show',
            $activity,
            __('Atividade criada com sucesso.'),
        );
    }

    public function show(Activity $activity): View
    {
        Gate::authorize('view', $activity);

        $activity->load([
            'company',
            'contact',
            'lead',
            'opportunity',
            'assignedUser',
            'createdByUser',
        ]);

        return view('activities.show', [
            'activity' => $activity,
        ]);
    }

    public function edit(Activity $activity): View
    {
        Gate::authorize('update', $activity);

        return view('activities.edit', [
            'activity' => $activity->load([
                'company',
                'contact',
                'lead',
                'opportunity',
                'assignedUser',
                'createdByUser',
            ]),
            'assignedUsers' => $this->users(
                $activity->assigned_user_id,
            ),
            'companies' => $this->companies(
                $activity->company_id,
            ),
            'contacts' => $this->contacts(
                $activity->contact_id,
            ),
            'leads' => $this->leads(
                $activity->lead_id,
            ),
            'opportunities' => $this->opportunities(
                $activity->opportunity_id,
            ),
        ]);
    }

    public function update(
        UpdateActivityRequest $request,
        Activity $activity,
        UpdateActivityAction $action,
    ): RedirectResponse {
        $activity = $action->execute(
            $activity,
            $request->validated(),
        );

        return $this->authorizedRedirect(
            $request->user(),
            'activities.show',
            $activity,
            __('Atividade atualizada com sucesso.'),
        );
    }

    public function destroy(
        Request $request,
        Activity $activity,
        DeleteActivityAction $action,
    ): RedirectResponse {
        Gate::authorize('delete', $activity);

        $action->execute($activity);

        return $this->authorizedRedirect(
            $request->user(),
            'activities.index',
            null,
            __('Atividade excluída com sucesso.'),
        );
    }

    public function mutationComplete(): View
    {
        return view('activities.mutation-complete');
    }

    /** @return Collection<int, User> */
    private function users(?int $includeId = null): Collection
    {
        return User::query()
            ->where(function ($query) use ($includeId): void {
                $query->where('active', true);

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'active']);
    }

    /** @return Collection<int, Company> */
    private function companies(?int $includeId = null): Collection
    {
        return Company::withTrashed()
            ->where(function ($query) use ($includeId): void {
                $query->whereNull('deleted_at');

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('legal_name')
            ->get([
                'id',
                'legal_name',
                'trade_name',
                'deleted_at',
            ]);
    }

    /** @return Collection<int, Contact> */
    private function contacts(?int $includeId = null): Collection
    {
        return Contact::withTrashed()
            ->where(function ($query) use ($includeId): void {
                $query->whereNull('deleted_at');

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->get([
                'id',
                'company_id',
                'name',
                'job_title',
                'deleted_at',
            ]);
    }

    /** @return Collection<int, Lead> */
    private function leads(?int $includeId = null): Collection
    {
        return Lead::withTrashed()
            ->where(function ($query) use ($includeId): void {
                $query->whereNull('deleted_at');

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->orderBy('company_name')
            ->get([
                'id',
                'name',
                'company_name',
                'deleted_at',
            ]);
    }

    /** @return Collection<int, Opportunity> */
    private function opportunities(
        ?int $includeId = null,
    ): Collection {
        return Opportunity::withTrashed()
            ->where(function ($query) use ($includeId): void {
                $query->whereNull('deleted_at');

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'company_id',
                'lead_id',
                'deleted_at',
            ]);
    }

    private function authorizedRedirect(
        User $user,
        string $route,
        ?Activity $activity,
        string $message,
    ): RedirectResponse {
        return $user->can('viewAny', Activity::class)
            ? redirect()
                ->route($route, $activity ?? [])
                ->with('status', $message)
            : redirect()
                ->route('activities.mutation-complete')
                ->with('status', $message);
    }
}

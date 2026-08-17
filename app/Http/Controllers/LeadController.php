<?php

namespace App\Http\Controllers;

use App\Actions\Leads\CreateLeadAction;
use App\Actions\Leads\DeleteLeadAction;
use App\Actions\Leads\UpdateLeadAction;
use App\Http\Requests\Leads\LeadIndexRequest;
use App\Http\Requests\Leads\StoreLeadRequest;
use App\Http\Requests\Leads\UpdateLeadRequest;
use App\Models\Channel;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use App\Queries\LeadIndexQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(
        LeadIndexRequest $request,
        LeadIndexQuery $query,
    ): View {
        return view('leads.index', [
            'leads' => $query->paginate($request->validated()),
            'sources' => $this->sources(),
            'channels' => $this->channels(),
            'assignedUsers' => $this->users(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Lead::class);

        return view('leads.create', [
            'sources' => $this->sources(),
            'channels' => $this->channels(),
            'assignedUsers' => $this->users(),
            'companies' => $this->companies(),
            'contacts' => $this->contacts(),
        ]);
    }

    public function store(
        StoreLeadRequest $request,
        CreateLeadAction $action,
    ): RedirectResponse {
        $lead = $action->execute($request->validated());

        return $this->authorizedRedirect(
            $request->user(),
            'leads.show',
            $lead,
            __('Lead criado com sucesso.'),
        );
    }

    public function show(Lead $lead): View
    {
        Gate::authorize('view', $lead);

        $lead->load([
            'company',
            'contact',
            'assignedUser',
            'source',
            'channel',
            'firstTouchSourceEvent',
            'lastTouchSourceEvent',
        ]);

        $events = $lead->sourceEvents()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'events_page')
            ->withQueryString();

        return view('leads.show', [
            'lead' => $lead,
            'events' => $events,
        ]);
    }

    public function edit(Lead $lead): View
    {
        Gate::authorize('update', $lead);

        return view('leads.edit', [
            'lead' => $lead->load([
                'company',
                'contact',
                'assignedUser',
                'source',
                'channel',
            ]),
            'sources' => $this->sources($lead->source_id),
            'channels' => $this->channels($lead->channel_id),
            'assignedUsers' => $this->users($lead->assigned_user_id),
            'companies' => $this->companies($lead->company_id),
            'contacts' => $this->contacts($lead->contact_id),
        ]);
    }

    public function update(
        UpdateLeadRequest $request,
        Lead $lead,
        UpdateLeadAction $action,
    ): RedirectResponse {
        $lead = $action->execute($lead, $request->validated());

        return $this->authorizedRedirect(
            $request->user(),
            'leads.show',
            $lead,
            __('Lead atualizado com sucesso.'),
        );
    }

    public function destroy(
        Request $request,
        Lead $lead,
        DeleteLeadAction $action,
    ): RedirectResponse {
        Gate::authorize('delete', $lead);

        $action->execute($lead);

        return $this->authorizedRedirect(
            $request->user(),
            'leads.index',
            null,
            __('Lead excluído com sucesso.'),
        );
    }

    public function mutationComplete(): View
    {
        return view('leads.mutation-complete');
    }

    /** @return Collection<int, LeadSource> */
    private function sources(?int $includeId = null): Collection
    {
        return LeadSource::query()
            ->where(function ($query) use ($includeId): void {
                $query->where('active', true);

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, Channel> */
    private function channels(?int $includeId = null): Collection
    {
        return Channel::query()
            ->where(function ($query) use ($includeId): void {
                $query->where('active', true);

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->get();
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
            ->get(['id', 'legal_name', 'trade_name', 'deleted_at']);
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
                'active',
                'deleted_at',
            ]);
    }

    private function authorizedRedirect(
        User $user,
        string $route,
        ?Lead $lead,
        string $message,
    ): RedirectResponse {
        return $user->can('viewAny', Lead::class)
            ? redirect()->route($route, $lead ?? [])->with('status', $message)
            : redirect()->route('leads.mutation-complete')->with('status', $message);
    }
}

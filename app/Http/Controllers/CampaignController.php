<?php

namespace App\Http\Controllers;

use App\Actions\Campaigns\AssociateLeadToCampaignAction;
use App\Actions\Campaigns\CreateCampaignAction;
use App\Actions\Campaigns\DeleteCampaignAction;
use App\Actions\Campaigns\UpdateCampaignAction;
use App\Http\Requests\Campaigns\AssociateLeadRequest;
use App\Http\Requests\Campaigns\CampaignIndexRequest;
use App\Http\Requests\Campaigns\StoreCampaignRequest;
use App\Http\Requests\Campaigns\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\Channel;
use App\Models\Lead;
use App\Models\User;
use App\Queries\CampaignIndexQuery;
use App\Queries\CampaignMetricsQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(CampaignIndexRequest $request, CampaignIndexQuery $query): View
    {
        return view('campaigns.index', [
            'campaigns' => $query->paginate($request->validated()),
            'channels' => $this->channels(),
            'owners' => $this->owners(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Campaign::class);

        return view('campaigns.create', ['channels' => $this->channels(), 'owners' => $this->owners()]);
    }

    public function store(StoreCampaignRequest $request, CreateCampaignAction $action): RedirectResponse
    {
        $campaign = $action->execute($request->validated());

        return $this->authorizedRedirect($request->user(), 'campaigns.show', $campaign, __('Campanha criada com sucesso.'));
    }

    public function show(Request $request, Campaign $campaign, CampaignMetricsQuery $metricsQuery): View
    {
        Gate::authorize('view', $campaign);

        $leads = Lead::query()
            ->whereHas('sourceEvents', fn ($query) => $query->where('campaign_id', $campaign->id))
            ->withMax(['sourceEvents as campaign_touched_at' => fn ($query) => $query->where('campaign_id', $campaign->id)], 'occurred_at')
            ->with(['company:id,legal_name,trade_name', 'assignedUser:id,name'])
            ->orderByDesc('campaign_touched_at')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'leads_page');

        $leadOptions = collect();
        $leadQuery = trim((string) $request->query('lead_q'));
        if ($request->user()->can('update', $campaign) && $request->user()->can('viewAny', Lead::class) && mb_strlen($leadQuery) >= 2) {
            $leadOptions = Lead::query()
                ->where(function ($query) use ($leadQuery): void {
                    $query->where('name', 'ilike', "%{$leadQuery}%")
                        ->orWhere('company_name', 'ilike', "%{$leadQuery}%")
                        ->orWhere('email', 'ilike', "%{$leadQuery}%");
                })
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'company_name']);
        }

        return view('campaigns.show', [
            'campaign' => $campaign->load(['channel:id,name,slug,active', 'owner:id,name,active']),
            'leads' => $leads,
            'leadOptions' => $leadOptions,
            'leadQuery' => $leadQuery,
            'metrics' => $metricsQuery->get($campaign),
        ]);
    }

    public function associateLead(AssociateLeadRequest $request, Campaign $campaign, AssociateLeadToCampaignAction $action): RedirectResponse
    {
        $lead = Lead::query()->findOrFail($request->integer('lead_id'));
        $event = $action->execute($campaign, $lead, $request->user());

        return redirect()->route('campaigns.show', $campaign)->with(
            'status',
            $event->wasRecentlyCreated ? __('Lead associado à campanha com sucesso.') : __('Este lead já está associado manualmente à campanha.'),
        );
    }

    public function edit(Campaign $campaign): View
    {
        Gate::authorize('update', $campaign);

        return view('campaigns.edit', [
            'campaign' => $campaign,
            'channels' => $this->channels($campaign->channel_id),
            'owners' => $this->owners($campaign->owner_user_id),
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign, UpdateCampaignAction $action): RedirectResponse
    {
        $campaign = $action->execute($campaign, $request->validated());

        return $this->authorizedRedirect($request->user(), 'campaigns.show', $campaign, __('Campanha atualizada com sucesso.'));
    }

    public function destroy(Request $request, Campaign $campaign, DeleteCampaignAction $action): RedirectResponse
    {
        Gate::authorize('delete', $campaign);
        $action->execute($campaign);

        return $this->authorizedRedirect($request->user(), 'campaigns.index', null, __('Campanha excluída com sucesso.'));
    }

    /** @return Collection<int, Channel> */
    private function channels(?int $includeId = null): Collection
    {
        return Channel::query()->where(function ($query) use ($includeId): void {
            $query->where('active', true);
            if ($includeId !== null) {
                $query->orWhere('id', $includeId);
            }
        })->orderBy('name')->get(['id', 'name', 'active']);
    }

    /** @return Collection<int, User> */
    private function owners(?int $includeId = null): Collection
    {
        return User::query()->where(function ($query) use ($includeId): void {
            $query->where('active', true);
            if ($includeId !== null) {
                $query->orWhere('id', $includeId);
            }
        })->orderBy('name')->get(['id', 'name', 'active']);
    }

    private function authorizedRedirect(User $user, string $route, ?Campaign $campaign, string $message): RedirectResponse
    {
        return $user->can('viewAny', Campaign::class)
            ? redirect()->route($route, $campaign ?? [])->with('status', $message)
            : redirect()->route('dashboard')->with('status', $message);
    }
}

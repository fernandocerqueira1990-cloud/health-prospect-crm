<?php

namespace App\Http\Controllers;

use App\Actions\Campaigns\CreateCampaignAction;
use App\Actions\Campaigns\DeleteCampaignAction;
use App\Actions\Campaigns\UpdateCampaignAction;
use App\Http\Requests\Campaigns\StoreCampaignRequest;
use App\Http\Requests\Campaigns\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Campaign::class);

        return view('campaigns.index', [
            'campaigns' => Campaign::query()
                ->with(['channel:id,name', 'owner:id,name'])
                ->latest('id')
                ->paginate(15),
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

    public function show(Campaign $campaign): View
    {
        Gate::authorize('view', $campaign);

        return view('campaigns.show', ['campaign' => $campaign->load(['channel:id,name,active', 'owner:id,name,active'])]);
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

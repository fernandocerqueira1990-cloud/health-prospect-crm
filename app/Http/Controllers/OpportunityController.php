<?php

namespace App\Http\Controllers;

use App\Actions\Opportunities\CreateOpportunityAction;
use App\Actions\Opportunities\DeleteOpportunityAction;
use App\Actions\Opportunities\MoveOpportunityStageAction;
use App\Actions\Opportunities\UpdateOpportunityAction;
use App\Http\Requests\Opportunities\MoveOpportunityStageRequest;
use App\Http\Requests\Opportunities\OpportunityIndexRequest;
use App\Http\Requests\Opportunities\StoreOpportunityRequest;
use App\Http\Requests\Opportunities\UpdateOpportunityRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\LossReason;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\User;
use App\Queries\OpportunityIndexQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function kanban(
        OpportunityIndexRequest $request,
    ): View {
        $filters = $request->validated();

        $pipelineId = isset($filters['pipeline_id'])
            ? (int) $filters['pipeline_id']
            : null;

        $pipeline = Pipeline::query()
            ->where('active', true)
            ->when(
                $pipelineId !== null,
                fn ($query) => $query->whereKey($pipelineId),
            )
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->firstOrFail();

        $stages = $this->stages($pipeline->id);

        $opportunityQuery = Opportunity::query()
            ->with([
                'lead:id,name,company_name',
                'company:id,legal_name,trade_name',
                'assignedUser:id,name',
                'stage:id,pipeline_id,name,slug,position,probability,type',
                'lossReason:id,name,slug',
            ])
            ->where('pipeline_id', $pipeline->id);

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $opportunityQuery->where(function ($query) use ($search): void {
                $query->where('title', 'ilike', '%'.$search.'%');

                $query->orWhereHas(
                    'company',
                    function ($company) use ($search): void {
                        $company
                            ->where('legal_name', 'ilike', '%'.$search.'%')
                            ->orWhere('trade_name', 'ilike', '%'.$search.'%');
                    },
                );

                $query->orWhereHas(
                    'lead',
                    function ($lead) use ($search): void {
                        $lead
                            ->where('name', 'ilike', '%'.$search.'%')
                            ->orWhere('company_name', 'ilike', '%'.$search.'%');
                    },
                );
            });
        }

        if (
            isset($filters['assigned_user_id'])
            && $filters['assigned_user_id'] !== ''
        ) {
            $opportunityQuery->where(
                'assigned_user_id',
                $filters['assigned_user_id'],
            );
        }

        $opportunitiesByStage = $opportunityQuery
            ->orderByDesc('amount')
            ->orderByDesc('id')
            ->get()
            ->groupBy('stage_id');

        return view('opportunities.kanban', [
            'pipeline' => $pipeline,
            'pipelines' => $this->pipelines(),
            'stages' => $stages,
            'assignedUsers' => $this->users(),
            'lossReasons' => $this->lossReasons(),
            'opportunitiesByStage' => $opportunitiesByStage,
        ]);
    }

    public function index(
        OpportunityIndexRequest $request,
        OpportunityIndexQuery $query,
    ): View {
        return view('opportunities.index', [
            'opportunities' => $query->paginate($request->validated()),
            'pipelines' => $this->pipelines(),
            'stages' => $this->stages(),
            'assignedUsers' => $this->users(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Opportunity::class);

        return view('opportunities.create', [
            'pipelines' => $this->pipelines(),
            'stages' => $this->stages(),
            'leads' => $this->leads(),
            'companies' => $this->companies(),
            'contacts' => $this->contacts(),
            'assignedUsers' => $this->users(),
            'lossReasons' => $this->lossReasons(),
        ]);
    }

    public function store(
        StoreOpportunityRequest $request,
        CreateOpportunityAction $action,
    ): RedirectResponse {
        $opportunity = $action->execute(
            $request->validated(),
            $request->user(),
        );

        return $this->authorizedRedirect(
            $request->user(),
            'opportunities.show',
            $opportunity,
            __('Oportunidade criada com sucesso.'),
        );
    }

    public function show(Opportunity $opportunity): View
    {
        Gate::authorize('view', $opportunity);

        $opportunity->load([
            'lead',
            'company',
            'contact',
            'assignedUser',
            'pipeline',
            'stage',
            'lossReason',
        ]);

        $histories = $opportunity->stageHistories()
            ->with([
                'fromStage:id,name',
                'toStage:id,name',
                'changedByUser:id,name',
            ])
            ->reorder('changed_at', 'desc')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'history_page')
            ->withQueryString();

        return view('opportunities.show', [
            'opportunity' => $opportunity,
            'histories' => $histories,
            'stages' => $this->stages($opportunity->pipeline_id),
            'lossReasons' => $this->lossReasons(
                $opportunity->loss_reason_id,
            ),
        ]);
    }

    public function edit(Opportunity $opportunity): View
    {
        Gate::authorize('update', $opportunity);

        return view('opportunities.edit', [
            'opportunity' => $opportunity->load([
                'lead',
                'company',
                'contact',
                'assignedUser',
                'pipeline',
                'stage',
                'lossReason',
            ]),
            'leads' => $this->leads($opportunity->lead_id),
            'companies' => $this->companies($opportunity->company_id),
            'contacts' => $this->contacts($opportunity->contact_id),
            'assignedUsers' => $this->users(
                $opportunity->assigned_user_id,
            ),
        ]);
    }

    public function update(
        UpdateOpportunityRequest $request,
        Opportunity $opportunity,
        UpdateOpportunityAction $action,
    ): RedirectResponse {
        $opportunity = $action->execute(
            $opportunity,
            $request->validated(),
        );

        return $this->authorizedRedirect(
            $request->user(),
            'opportunities.show',
            $opportunity,
            __('Oportunidade atualizada com sucesso.'),
        );
    }

    public function destroy(
        Request $request,
        Opportunity $opportunity,
        DeleteOpportunityAction $action,
    ): RedirectResponse {
        Gate::authorize('delete', $opportunity);

        $action->execute($opportunity);

        return $this->authorizedRedirect(
            $request->user(),
            'opportunities.index',
            null,
            __('Oportunidade excluída com sucesso.'),
        );
    }

    public function moveStage(
        MoveOpportunityStageRequest $request,
        Opportunity $opportunity,
        MoveOpportunityStageAction $action,
    ): RedirectResponse|JsonResponse {
        $data = $request->validated();

        $stage = Stage::query()->findOrFail($data['stage_id']);

        $lossReason = isset($data['loss_reason_id'])
            ? LossReason::query()->findOrFail($data['loss_reason_id'])
            : null;

        $opportunity = $action->execute(
            $opportunity,
            $stage,
            $request->user(),
            $data['notes'] ?? null,
            $lossReason,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Etapa da oportunidade atualizada com sucesso.',
                'opportunity' => [
                    'id' => $opportunity->id,
                    'stage_id' => $opportunity->stage_id,
                    'probability' => $opportunity->probability,
                    'loss_reason_id' => $opportunity->loss_reason_id,
                    'won_at' => $opportunity->won_at !== null
                        ? Carbon::parse($opportunity->won_at)->toISOString()
                        : null,
                    'lost_at' => $opportunity->lost_at !== null
                        ? Carbon::parse($opportunity->lost_at)->toISOString()
                        : null,
                ],
            ]);
        }

        return $this->authorizedRedirect(
            $request->user(),
            'opportunities.show',
            $opportunity,
            __('Etapa da oportunidade atualizada com sucesso.'),
        );
    }

    public function mutationComplete(): View
    {
        return view('opportunities.mutation-complete');
    }

    /** @return Collection<int, Pipeline> */
    private function pipelines(): Collection
    {
        return Pipeline::query()
            ->where('active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, Stage> */
    private function stages(?int $pipelineId = null): Collection
    {
        return Stage::query()
            ->where('active', true)
            ->when(
                $pipelineId !== null,
                fn ($query) => $query->where('pipeline_id', $pipelineId),
            )
            ->orderBy('pipeline_id')
            ->orderBy('position')
            ->get();
    }

    /** @return Collection<int, LossReason> */
    private function lossReasons(?int $includeId = null): Collection
    {
        return LossReason::query()
            ->where(function ($query) use ($includeId): void {
                $query->where('active', true);

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('position')
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
            ->get([
                'id',
                'name',
                'company_name',
                'company_id',
                'contact_id',
                'deleted_at',
            ]);
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

    private function authorizedRedirect(
        User $user,
        string $route,
        ?Opportunity $opportunity,
        string $message,
    ): RedirectResponse {
        return $user->can('viewAny', Opportunity::class)
            ? redirect()
                ->route($route, $opportunity ?? [])
                ->with('status', $message)
            : redirect()
                ->route('opportunities.mutation-complete')
                ->with('status', $message);
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\CompleteFollowUpAction;
use App\Actions\Tasks\CreateTaskAction;
use App\Actions\Tasks\DeleteTaskAction;
use App\Actions\Tasks\UpdateTaskAction;
use App\Http\Requests\Tasks\CompleteFollowUpRequest;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\TaskIndexRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use App\Queries\TaskIndexQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(
        TaskIndexRequest $request,
        TaskIndexQuery $query,
    ): View {
        return view('tasks.index', [
            'tasks' => $query->paginate($request->validated()),
            'assignedUsers' => $this->users(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Task::class);

        return view('tasks.create', [
            'assignedUsers' => $this->users(),
            'companies' => $this->companies(),
            'contacts' => $this->contacts(),
            'leads' => $this->leads(),
            'opportunities' => $this->opportunities(),
        ]);
    }

    public function store(
        StoreTaskRequest $request,
        CreateTaskAction $action,
    ): RedirectResponse {
        $task = $action->execute(
            $request->validated(),
            $request->user(),
        );

        return $this->authorizedRedirect(
            $request->user(),
            'tasks.show',
            $task,
            __('Tarefa criada com sucesso.'),
        );
    }

    public function show(Task $task): View
    {
        Gate::authorize('view', $task);

        $task->load([
            'company',
            'contact',
            'lead',
            'opportunity',
            'assignedUser',
            'createdByUser',
        ]);

        return view('tasks.show', [
            'task' => $task,
        ]);
    }

    public function edit(Task $task): View
    {
        Gate::authorize('update', $task);

        return view('tasks.edit', [
            'task' => $task->load([
                'company',
                'contact',
                'lead',
                'opportunity',
                'assignedUser',
                'createdByUser',
            ]),
            'assignedUsers' => $this->users(
                $task->assigned_user_id,
            ),
            'companies' => $this->companies(
                $task->company_id,
            ),
            'contacts' => $this->contacts(
                $task->contact_id,
            ),
            'leads' => $this->leads(
                $task->lead_id,
            ),
            'opportunities' => $this->opportunities(
                $task->opportunity_id,
            ),
        ]);
    }

    public function update(
        UpdateTaskRequest $request,
        Task $task,
        UpdateTaskAction $action,
    ): RedirectResponse {
        $data = $request->validated();

        if (
            $task->is_follow_up
            && ($data['status'] ?? null) === 'completed'
            && $task->status !== 'completed'
        ) {
            return back()
                ->withErrors([
                    'status' => 'Conclua o follow-up pela ação específica de conclusão.',
                ])
                ->withInput();
        }

        $task = $action->execute(
            $task,
            $data,
        );

        return $this->authorizedRedirect(
            $request->user(),
            'tasks.show',
            $task,
            __('Tarefa atualizada com sucesso.'),
        );
    }

    public function completeFollowUp(
        CompleteFollowUpRequest $request,
        Task $task,
        CompleteFollowUpAction $action,
    ): RedirectResponse {
        $task = $action->execute(
            $task,
            $request->user(),
            $request->validated('outcome'),
        );

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', __('Follow-up concluído e atividade registrada.'));
    }

    public function destroy(
        Request $request,
        Task $task,
        DeleteTaskAction $action,
    ): RedirectResponse {
        Gate::authorize('delete', $task);

        $action->execute($task);

        return $this->authorizedRedirect(
            $request->user(),
            'tasks.index',
            null,
            __('Tarefa excluída com sucesso.'),
        );
    }

    public function mutationComplete(): View
    {
        return view('tasks.mutation-complete');
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
        ?Task $task,
        string $message,
    ): RedirectResponse {
        return $user->can('viewAny', Task::class)
            ? redirect()
                ->route($route, $task ?? [])
                ->with('status', $message)
            : redirect()
                ->route('tasks.mutation-complete')
                ->with('status', $message);
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Companies\CreateCompanyAction;
use App\Actions\Companies\DeleteCompanyAction;
use App\Actions\Companies\UpdateCompanyAction;
use App\Http\Requests\Companies\CompanyIndexRequest;
use App\Http\Requests\Companies\StoreCompanyRequest;
use App\Http\Requests\Companies\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Queries\CompanyIndexQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(CompanyIndexRequest $request, CompanyIndexQuery $companyQuery): View
    {
        return view('companies.index', [
            'companies' => $companyQuery->paginate($request->validated()),
            'assignedUsers' => $this->activeUsers(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Company::class);

        return view('companies.create', ['assignedUsers' => $this->activeUsers()]);
    }

    public function store(StoreCompanyRequest $request, CreateCompanyAction $action): RedirectResponse
    {
        $company = $action->execute($request->validated());

        return $this->authorizedRedirect(
            $request->user(),
            'companies.show',
            $company,
            __('Empresa criada com sucesso.'),
        );
    }

    public function show(Company $company): View
    {
        Gate::authorize('view', $company);
        $company->load('assignedUser');
        $contacts = null;
        if (Gate::allows('viewAny', Contact::class)) {
            $contacts = $company->contacts()
                ->orderByDesc('is_primary')
                ->orderBy('name')
                ->paginate(10, ['*'], 'contacts_page')
                ->withQueryString()
                ->fragment('contatos');
        }

        return view('companies.show', ['company' => $company, 'contacts' => $contacts]);
    }

    public function edit(Company $company): View
    {
        Gate::authorize('update', $company);

        return view('companies.edit', [
            'company' => $company->load('assignedUser'),
            'assignedUsers' => $this->activeUsers($company->assigned_user_id),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company, UpdateCompanyAction $action): RedirectResponse
    {
        $action->execute($company, $request->validated());

        return $this->authorizedRedirect(
            $request->user(),
            'companies.show',
            $company,
            __('Empresa atualizada com sucesso.'),
        );
    }

    public function destroy(Request $request, Company $company, DeleteCompanyAction $action): RedirectResponse
    {
        Gate::authorize('delete', $company);
        $action->execute($company);

        return $this->authorizedRedirect(
            $request->user(),
            'companies.index',
            null,
            __('Empresa excluída com sucesso.'),
        );
    }

    public function mutationComplete(): View
    {
        return view('companies.mutation-complete');
    }

    /** @return Collection<int, User> */
    private function activeUsers(?int $includeUserId = null): Collection
    {
        return User::query()
            ->where(function ($query) use ($includeUserId): void {
                $query->where('active', true);
                if ($includeUserId !== null) {
                    $query->orWhere('id', $includeUserId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'active']);
    }

    private function authorizedRedirect(User $user, string $viewRoute, ?Company $company, string $message): RedirectResponse
    {
        if ($user->can('viewAny', Company::class)) {
            return redirect()->route($viewRoute, $company ?? [])->with('status', $message);
        }

        return redirect()->route('companies.mutation-complete')->with('status', $message);
    }
}

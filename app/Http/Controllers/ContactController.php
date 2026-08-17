<?php

namespace App\Http\Controllers;

use App\Actions\Contacts\CreateContactAction;
use App\Actions\Contacts\DeleteContactAction;
use App\Actions\Contacts\UpdateContactAction;
use App\Http\Requests\Contacts\ContactIndexRequest;
use App\Http\Requests\Contacts\StoreContactRequest;
use App\Http\Requests\Contacts\UpdateContactRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Queries\ContactIndexQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(ContactIndexRequest $request, ContactIndexQuery $query): View
    {
        return view('contacts.index', ['contacts' => $query->paginate($request->validated()), 'companies' => $this->companies()]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Contact::class);
        $selectedCompanyId = $request->integer('company_id') ?: null;

        return view('contacts.create', ['companies' => $this->companies(), 'selectedCompanyId' => $selectedCompanyId]);
    }

    public function store(StoreContactRequest $request, CreateContactAction $action): RedirectResponse
    {
        $contact = $action->execute($request->validated());

        return $this->authorizedRedirect($request->user(), 'contacts.show', $contact, __('Contato criado com sucesso.'));
    }

    public function show(Contact $contact): View
    {
        Gate::authorize('view', $contact);

        return view('contacts.show', ['contact' => $contact->load('company')]);
    }

    public function edit(Contact $contact): View
    {
        Gate::authorize('update', $contact);

        return view('contacts.edit', [
            'contact' => $contact->load('company'),
            'companies' => $this->companies($contact->company_id),
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact, UpdateContactAction $action): RedirectResponse
    {
        $contact = $action->execute($contact, $request->validated());

        return $this->authorizedRedirect($request->user(), 'contacts.show', $contact, __('Contato atualizado com sucesso.'));
    }

    public function destroy(Request $request, Contact $contact, DeleteContactAction $action): RedirectResponse
    {
        Gate::authorize('delete', $contact);
        $action->execute($contact);

        return $this->authorizedRedirect($request->user(), 'contacts.index', null, __('Contato excluído com sucesso.'));
    }

    public function mutationComplete(): View
    {
        return view('contacts.mutation-complete');
    }

    /** @return Collection<int, Company> */
    private function companies(?int $includeCompanyId = null): Collection
    {
        return Company::withTrashed()
            ->where(function ($query) use ($includeCompanyId): void {
                $query->whereNull('deleted_at');
                if ($includeCompanyId !== null) {
                    $query->orWhere('id', $includeCompanyId);
                }
            })
            ->orderBy('legal_name')
            ->get(['id', 'legal_name', 'trade_name', 'deleted_at']);
    }

    private function authorizedRedirect(User $user, string $route, ?Contact $contact, string $message): RedirectResponse
    {
        return $user->can('viewAny', Contact::class)
            ? redirect()->route($route, $contact ?? [])->with('status', $message)
            : redirect()->route('contacts.mutation-complete')->with('status', $message);
    }
}

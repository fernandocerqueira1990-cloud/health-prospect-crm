<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Services\AuditService;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateContactAction
{
    public function __construct(
        private readonly LockContactCompaniesAction $lockCompanies,
        private readonly SetPrimaryContactAction $setPrimary,
        private readonly AuditService $audit,
    ) {}

    public function execute(Contact $contact, array $data): Contact
    {
        return DB::transaction(function () use ($contact, $data): Contact {
            $originalCompanyId = (int) Contact::query()->whereKey($contact->getKey())->valueOrFail('company_id');
            $targetCompanyId = (int) $data['company_id'];
            $companies = $this->lockCompanies->execute([$originalCompanyId, $targetCompanyId]);
            $contact = Contact::query()->lockForUpdate()->findOrFail($contact->getKey());
            if ($contact->company_id !== $originalCompanyId) {
                throw new RecordsNotFoundException('O vínculo do contato mudou durante a atualização. Tente novamente.');
            }
            $targetCompany = $companies->firstWhere('id', $targetCompanyId);
            if ($targetCompany === null || ($targetCompany->trashed() && $targetCompanyId !== $originalCompanyId)) {
                throw ValidationException::withMessages(['company_id' => __('A empresa selecionada deve existir e estar ativa.')]);
            }
            $before = $contact->attributesToArray();
            if (! $data['active']) {
                $data['is_primary'] = false;
            }
            if ($data['is_primary'] && $data['active']) {
                $this->setPrimary->execute($targetCompany, (int) $contact->getKey());
            }
            $contact->update($data);
            $contact->refresh();
            $this->audit->record('contact_updated', $contact, $before, $contact->attributesToArray());
            if ($before['active'] !== $contact->active) {
                $this->audit->record($contact->active ? 'contact_activated' : 'contact_deactivated', $contact, $before, $contact->attributesToArray());
            }
            if (! $before['is_primary'] && $contact->is_primary) {
                $this->audit->record('contact_marked_primary', $contact, $before, $contact->attributesToArray());
            }

            return $contact;
        });
    }
}

<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Services\AuditService;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Facades\DB;

class DeleteContactAction
{
    public function __construct(private readonly LockContactCompaniesAction $lockCompanies, private readonly AuditService $audit) {}

    public function execute(Contact $contact): void
    {
        DB::transaction(function () use ($contact): void {
            $companyId = (int) Contact::query()->whereKey($contact->getKey())->valueOrFail('company_id');
            $this->lockCompanies->execute([$companyId]);
            $contact = Contact::query()->lockForUpdate()->findOrFail($contact->getKey());
            if ($contact->company_id !== $companyId) {
                throw new RecordsNotFoundException('O vínculo do contato mudou durante a exclusão. Tente novamente.');
            }
            $before = $contact->attributesToArray();
            $contact->delete();
            $this->audit->record('contact_deleted', $contact, $before, $contact->attributesToArray());
        });
    }
}

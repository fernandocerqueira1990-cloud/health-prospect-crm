<?php

namespace App\Actions\Contacts;

use App\Models\Company;
use App\Models\Contact;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateContactAction
{
    public function __construct(private readonly SetPrimaryContactAction $setPrimary, private readonly AuditService $audit) {}

    public function execute(array $data): Contact
    {
        return DB::transaction(function () use ($data): Contact {
            $company = Company::query()->lockForUpdate()->findOrFail($data['company_id']);
            if (! $data['active']) {
                $data['is_primary'] = false;
            }
            if ($data['is_primary'] && $data['active']) {
                $this->setPrimary->execute($company);
            }
            $contact = Contact::create($data);
            $this->audit->record('contact_created', $contact, after: $contact->attributesToArray());
            if ($contact->is_primary) {
                $this->audit->record('contact_marked_primary', $contact, after: $contact->attributesToArray());
            }

            return $contact;
        });
    }
}

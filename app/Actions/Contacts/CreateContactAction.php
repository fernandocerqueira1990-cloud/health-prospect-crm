<?php

namespace App\Actions\Contacts;

use App\Models\Company;
use App\Models\Contact;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateContactAction
{
    public function __construct(private readonly SetPrimaryContactAction $setPrimary, private readonly AuditService $audit) {}

    /** @param array<string, mixed> $data @param array<string, mixed>|null $auditAfter */
    public function execute(array $data, ?array $auditAfter = null): Contact
    {
        return DB::transaction(function () use ($data, $auditAfter): Contact {
            $company = Company::query()->lockForUpdate()->findOrFail($data['company_id']);
            if (! $data['active']) {
                $data['is_primary'] = false;
            }
            if ($data['is_primary'] && $data['active']) {
                $this->setPrimary->execute($company);
            }
            $contact = Contact::create($data);
            $this->audit->record('contact_created', $contact, after: $auditAfter ?? $contact->attributesToArray());
            if ($contact->is_primary) {
                $this->audit->record('contact_marked_primary', $contact, after: $auditAfter ?? $contact->attributesToArray());
            }

            return $contact;
        });
    }
}

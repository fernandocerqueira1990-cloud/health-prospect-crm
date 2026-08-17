<?php

namespace App\Actions\Contacts;

use App\Models\Company;
use App\Models\Contact;

class SetPrimaryContactAction
{
    public function execute(Company $lockedCompany, ?int $exceptContactId = null): void
    {
        $query = Contact::query()->where('company_id', $lockedCompany->getKey())->where('is_primary', true);
        if ($exceptContactId !== null) {
            $query->whereKeyNot($exceptContactId);
        }
        $query->update(['is_primary' => false]);
    }
}

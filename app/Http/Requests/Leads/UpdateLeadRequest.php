<?php

namespace App\Http\Requests\Leads;

use App\Models\Lead;

class UpdateLeadRequest extends StoreLeadRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead instanceof Lead
            && $this->user()->can('update', $lead);
    }

    public function rules(): array
    {
        $lead = $this->route('lead');

        return $this->leadRules(
            $lead instanceof Lead ? $lead : null,
        );
    }
}

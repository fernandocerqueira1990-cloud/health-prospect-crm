<?php

namespace App\Http\Requests\Contacts;

use App\Models\Contact;
use Illuminate\Validation\Rule;

class UpdateContactRequest extends StoreContactRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('contact'));
    }

    public function rules(): array
    {
        /** @var Contact $contact */
        $contact = $this->route('contact');
        $rules = parent::rules();
        $rules['company_id'] = [
            'required',
            'integer',
            Rule::exists('companies', 'id')->where(function ($query) use ($contact): void {
                $query->whereNull('deleted_at')->orWhere('id', $contact->company_id);
            }),
        ];

        return $rules;
    }
}

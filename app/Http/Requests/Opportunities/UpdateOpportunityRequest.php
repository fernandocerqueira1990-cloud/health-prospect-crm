<?php

namespace App\Http\Requests\Opportunities;

use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $opportunity = $this->route('opportunity');

        return $opportunity instanceof Opportunity
            && $this->user()->can('update', $opportunity);
    }

    public function rules(): array
    {
        $companyId = $this->integer('company_id') ?: null;

        $contactExists = Rule::exists('contacts', 'id')
            ->where(function ($query) use ($companyId): void {
                $query->whereNull('deleted_at');

                if ($companyId !== null) {
                    $query->where('company_id', $companyId);
                }
            });

        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'lead_id' => [
                'nullable',
                'required_without:company_id',
                'integer',
                Rule::exists('leads', 'id')
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],

            'company_id' => [
                'nullable',
                'required_without:lead_id',
                'required_with:contact_id',
                'integer',
                Rule::exists('companies', 'id')
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],

            'contact_id' => [
                'nullable',
                'integer',
                $contactExists,
            ],

            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where(fn ($query) => $query->where('active', true)),
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],

            'expected_close_date' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ];
    }
}

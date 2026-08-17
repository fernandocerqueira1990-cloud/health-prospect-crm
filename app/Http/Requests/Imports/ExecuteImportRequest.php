<?php

namespace App\Http\Requests\Imports;

use App\Models\DataImport;
use App\Services\ImportExecutionPrerequisites;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExecuteImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dataImport = $this->route('dataImport');

        return $dataImport instanceof DataImport && $this->user()->can('update', $dataImport);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $dataImport = $this->route('dataImport');
        $needsChannel = $dataImport instanceof DataImport && app(ImportExecutionPrerequisites::class)->needsLeadChannel($dataImport);

        return [
            'lead_channel_id' => [$needsChannel ? 'required' : 'nullable', 'integer', Rule::exists('channels', 'id')->where('active', true)],
            'source_id' => ['prohibited'],
            'channel_id' => ['prohibited'],
            'assigned_user_id' => ['prohibited'],
            'company_id' => ['prohibited'],
            'contact_id' => ['prohibited'],
        ];
    }
}

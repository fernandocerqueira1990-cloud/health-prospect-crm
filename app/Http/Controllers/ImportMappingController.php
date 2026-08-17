<?php

namespace App\Http\Controllers;

use App\Actions\Imports\UpdateImportMappingAction;
use App\Http\Requests\Imports\UpdateImportMappingRequest;
use App\Models\DataImport;
use App\Services\ImportMappingViewData;
use App\Support\ImportFieldCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ImportMappingController extends Controller
{
    public function edit(DataImport $dataImport, ImportFieldCatalog $catalog, ImportMappingViewData $viewData): View
    {
        Gate::authorize('update', $dataImport);
        abort_unless($dataImport->status === DataImport::STATUS_PARSED, 409, 'Somente importações interpretadas podem ser mapeadas.');

        $headers = $dataImport->metadata['header'] ?? [];
        abort_unless(is_array($headers) && array_filter($headers, 'is_string') === $headers, 409, 'Cabeçalho inválido para mapeamento.');
        $headers = array_values($headers);
        $savedMapping = $dataImport->metadata['mapping']['columns'] ?? [];

        return view('imports.mapping', [
            'dataImport' => $dataImport,
            'headers' => $headers,
            'groups' => $catalog->groups(),
            'samples' => $viewData->samples($dataImport, $headers),
            'savedMapping' => $savedMapping,
            'selections' => collect($headers)->mapWithKeys(fn (string $header): array => [$header => $savedMapping[$header] ?? $catalog->suggest($header)])->all(),
        ]);
    }

    public function update(UpdateImportMappingRequest $request, DataImport $dataImport, UpdateImportMappingAction $action): RedirectResponse
    {
        $action->execute($dataImport, $request->validated('columns'), $request->user());

        return redirect()->route('imports.mapping.edit', $dataImport)->with('status', __('Mapeamento atualizado com sucesso.'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\ImportPreviewRequest;
use App\Models\DataImport;
use App\Services\ImportPreviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ImportPreviewController extends Controller
{
    public function __invoke(ImportPreviewRequest $request, DataImport $dataImport, ImportPreviewService $preview): View|RedirectResponse
    {
        abort_unless($dataImport->status === DataImport::STATUS_PARSED, 409, 'Somente importações interpretadas podem ser visualizadas no Preview.');

        if (! $preview->hasValidMapping($dataImport) || ! $preview->hasNormalizedData($dataImport)) {
            return redirect()->route('imports.show', $dataImport)->with('status', 'Mapeie as colunas antes de visualizar o Preview.');
        }

        $validated = $request->validated();
        $filter = is_string($validated['status'] ?? null) ? $validated['status'] : 'all';
        $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 25;
        $page = isset($validated['page']) ? (int) $validated['page'] : 1;
        $result = $preview->build($dataImport, $filter, $perPage, $page, $request->url(), $request->query());

        return view('imports.preview', [
            'dataImport' => $dataImport,
            'rows' => $result['rows'],
            'counts' => $result['counts'],
            'filter' => $filter,
            'perPage' => $perPage,
            'mappedCount' => count($dataImport->metadata['mapping']['columns']),
        ]);
    }
}

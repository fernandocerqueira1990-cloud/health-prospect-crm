<?php

namespace App\Http\Controllers;

use App\Actions\Imports\AnalyzeImportDedupAction;
use App\Actions\Imports\UpdateImportDedupDecisionAction;
use App\Http\Requests\Imports\AnalyzeImportDedupRequest;
use App\Http\Requests\Imports\UpdateImportDedupDecisionRequest;
use App\Models\DataImport;
use App\Models\ImportRow;
use App\Services\ImportDedupViewData;
use App\Services\ImportPreviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ImportDedupController extends Controller
{
    public function index(DataImport $dataImport, ImportPreviewService $preview, ImportDedupViewData $viewData): View|RedirectResponse
    {
        Gate::authorize('view', $dataImport);
        abort_unless($dataImport->status === DataImport::STATUS_PARSED, 409, 'Somente importações interpretadas podem ser analisadas.');
        if (! $preview->hasValidMapping($dataImport) || ! $preview->hasNormalizedData($dataImport)) {
            return redirect()->route('imports.show', $dataImport)->with('status', 'Mapeie as colunas antes de analisar duplicidades.');
        }

        return view('imports.dedup', ['dataImport' => $dataImport] + $viewData->build($dataImport, request()->user()));
    }

    public function analyze(AnalyzeImportDedupRequest $request, DataImport $dataImport, AnalyzeImportDedupAction $action): RedirectResponse
    {
        $action->execute($dataImport, $request->user());

        return redirect()->route('imports.dedup.index', $dataImport)->with('status', 'Análise de duplicidades concluída.');
    }

    public function update(UpdateImportDedupDecisionRequest $request, DataImport $dataImport, ImportRow $importRow, UpdateImportDedupDecisionAction $action): RedirectResponse
    {
        $validated = $request->validated();
        $action->execute($dataImport, $importRow, $validated['group'], $validated['action'], $validated['candidate_ref'] ?? null, $request->user());

        return redirect()->route('imports.dedup.index', $dataImport)->with('status', 'Decisão de deduplicação atualizada.');
    }
}

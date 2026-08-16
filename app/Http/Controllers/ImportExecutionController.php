<?php

namespace App\Http\Controllers;

use App\Actions\Imports\ExecuteImportAction;
use App\Exceptions\ImportExecutionException;
use App\Http\Requests\Imports\ExecuteImportRequest;
use App\Models\Channel;
use App\Models\DataImport;
use App\Services\ImportExecutionPrerequisites;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ImportExecutionController extends Controller
{
    public function confirm(DataImport $dataImport, ImportExecutionPrerequisites $prerequisites): View|RedirectResponse
    {
        Gate::authorize('view', $dataImport);
        if ($dataImport->status === DataImport::STATUS_COMPLETED) {
            return redirect()->route('imports.report', $dataImport);
        }
        try {
            $prerequisites->validate($dataImport);
        } catch (ImportExecutionException $exception) {
            return redirect()->route('imports.dedup.index', $dataImport)->withErrors(['execution' => $exception->getMessage()]);
        }
        $needsLeadChannel = $prerequisites->needsLeadChannel($dataImport);

        return view('imports.execute', [
            'dataImport' => $dataImport,
            'needsLeadChannel' => $needsLeadChannel,
            'channels' => $needsLeadChannel ? Channel::query()->where('active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'dedupSummary' => $dataImport->metadata['dedup']['summary'],
        ]);
    }

    public function execute(ExecuteImportRequest $request, DataImport $dataImport, ExecuteImportAction $action): RedirectResponse
    {
        try {
            $action->execute($dataImport, $request->integer('lead_channel_id') ?: null, $request->user());
        } catch (ImportExecutionException $exception) {
            return back()->withErrors(['execution' => $exception->getMessage()]);
        }

        return redirect()->route('imports.report', $dataImport)->with('status', 'Importação executada. Consulte o relatório final.');
    }
}

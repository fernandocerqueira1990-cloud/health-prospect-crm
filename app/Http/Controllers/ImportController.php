<?php

namespace App\Http\Controllers;

use App\Actions\Imports\CreateCsvImportAction;
use App\Actions\Imports\CreateXlsxImportAction;
use App\Actions\Imports\DeleteImportAction;
use App\Http\Requests\Imports\StoreImportRequest;
use App\Models\DataImport;
use App\Queries\ImportIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(ImportIndexQuery $query): View
    {
        Gate::authorize('viewAny', DataImport::class);

        return view('imports.index', ['imports' => $query->paginate()]);
    }

    public function create(): View
    {
        Gate::authorize('create', DataImport::class);

        return view('imports.create', ['maxUploadMb' => round((int) config('imports.max_upload_kb') / 1024, 1)]);
    }

    public function store(StoreImportRequest $request, CreateCsvImportAction $csvAction, CreateXlsxImportAction $xlsxAction): RedirectResponse
    {
        $file = $request->file('file');
        $isXlsx = strtolower($file->getClientOriginalExtension()) === DataImport::TYPE_XLSX;
        $dataImport = $isXlsx
            ? $xlsxAction->execute($file, $request->user())
            : $csvAction->execute($file, $request->user());

        return redirect()->route('imports.show', $dataImport)->with('status', $dataImport->status === DataImport::STATUS_PARSED ? __('Arquivo interpretado com sucesso.') : __('O arquivo foi armazenado, mas não pôde ser interpretado.'));
    }

    public function show(DataImport $dataImport): View
    {
        Gate::authorize('view', $dataImport);

        return view('imports.show', ['dataImport' => $dataImport->load('user:id,name')]);
    }

    public function destroy(Request $request, DataImport $dataImport, DeleteImportAction $action): RedirectResponse
    {
        Gate::authorize('delete', $dataImport);
        $action->execute($dataImport);

        return redirect()->route('imports.index')->with('status', __('Importação excluída com sucesso.'));
    }
}

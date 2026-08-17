<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\ImportReportRequest;
use App\Models\DataImport;
use App\Services\ImportReportService;
use Illuminate\View\View;

class ImportReportController extends Controller
{
    public function __invoke(ImportReportRequest $request, DataImport $dataImport, ImportReportService $report): View
    {
        abort_unless(isset($dataImport->metadata['execution']), 409, 'A importação ainda não possui execução final.');
        $status = is_string($request->validated('status')) ? $request->validated('status') : 'all';

        return view('imports.report', ['dataImport' => $dataImport, 'filter' => $status] + $report->build($dataImport, $status, $request->user()));
    }
}

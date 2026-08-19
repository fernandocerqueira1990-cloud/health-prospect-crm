<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reports\ReportIndexRequest;
use App\Queries\Reports\AcquisitionReportQuery;
use App\Queries\Reports\CommercialSummaryQuery;
use App\Queries\Reports\ExecutiveCommercialQuery;
use App\Queries\Reports\FunnelReportQuery;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(
        ReportIndexRequest $request,
        CommercialSummaryQuery $summaryQuery,
        ExecutiveCommercialQuery $executiveQuery,
        FunnelReportQuery $funnelQuery,
        AcquisitionReportQuery $acquisitionQuery,
    ): View {
        $filters = $request->validated();

        return view('reports.index', [
            'filters' => $filters,
            'metrics' => $summaryQuery->get($filters),
            'executiveMetrics' => $executiveQuery->get($filters),
            'funnels' => $funnelQuery->get($filters),
            'acquisition' => $acquisitionQuery->get($filters),
        ]);
    }
}

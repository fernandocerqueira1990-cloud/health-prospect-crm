<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reports\ReportIndexRequest;
use App\Queries\Reports\CommercialSummaryQuery;
use App\Queries\Reports\ExecutiveCommercialQuery;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(
        ReportIndexRequest $request,
        CommercialSummaryQuery $summaryQuery,
        ExecutiveCommercialQuery $executiveQuery,
    ): View {
        $filters = $request->validated();

        return view('reports.index', [
            'filters' => $filters,
            'metrics' => $summaryQuery->get($filters),
            'executiveMetrics' => $executiveQuery->get($filters),
        ]);
    }
}

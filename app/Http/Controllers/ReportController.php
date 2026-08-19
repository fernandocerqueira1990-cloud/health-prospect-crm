<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reports\ReportIndexRequest;
use App\Queries\Reports\CommercialSummaryQuery;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(ReportIndexRequest $request, CommercialSummaryQuery $query): View
    {
        $filters = $request->validated();

        return view('reports.index', [
            'filters' => $filters,
            'metrics' => $query->get($filters),
        ]);
    }
}

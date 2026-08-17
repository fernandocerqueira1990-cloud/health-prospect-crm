<?php

namespace App\Http\Controllers;

use App\Http\Requests\Timeline\TimelineIndexRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Queries\CommercialTimelineQuery;
use Illuminate\View\View;

class TimelineController extends Controller
{
    public function index(
        TimelineIndexRequest $request,
        CommercialTimelineQuery $query,
    ): View {
        return view('timeline.index', [
            'timeline' => $query->paginate($request->validated()),

            'companies' => Company::query()
                ->orderBy('legal_name')
                ->get(['id', 'legal_name', 'trade_name']),

            'contacts' => Contact::query()
                ->orderBy('name')
                ->get(['id', 'name']),

            'leads' => Lead::query()
                ->orderBy('name')
                ->orderBy('company_name')
                ->get(['id', 'name', 'company_name']),

            'opportunities' => Opportunity::query()
                ->orderBy('title')
                ->get(['id', 'title']),

            'assignedUsers' => User::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}

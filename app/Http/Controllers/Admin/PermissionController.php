<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Permission::class);

        return view('admin.permissions.index', ['permissions' => Permission::withCount('roles')->orderBy('slug')->paginate(25)]);
    }
}

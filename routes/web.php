<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ImportDedupController;
use App\Http\Controllers\ImportExecutionController;
use App\Http\Controllers\ImportMappingController;
use App\Http\Controllers\ImportPreviewController;
use App\Http\Controllers\ImportReportController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimelineController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
    ]);
})->name('health');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register')
        ->name('register.store');
});

Route::middleware(['auth', EnsureUserIsActive::class])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/imports/{dataImport}/mapping', [ImportMappingController::class, 'edit'])->name('imports.mapping.edit');
    Route::put('/imports/{dataImport}/mapping', [ImportMappingController::class, 'update'])->name('imports.mapping.update');
    Route::get('/imports/{dataImport}/preview', ImportPreviewController::class)->name('imports.preview');
    Route::get('/imports/{dataImport}/dedup', [ImportDedupController::class, 'index'])->name('imports.dedup.index');
    Route::post('/imports/{dataImport}/dedup/analyze', [ImportDedupController::class, 'analyze'])->name('imports.dedup.analyze');
    Route::put('/imports/{dataImport}/dedup/{importRow}', [ImportDedupController::class, 'update'])->name('imports.dedup.update');
    Route::get('/imports/{dataImport}/execute', [ImportExecutionController::class, 'confirm'])->name('imports.execute.confirm');
    Route::post('/imports/{dataImport}/execute', [ImportExecutionController::class, 'execute'])->name('imports.execute');
    Route::get('/imports/{dataImport}/report', ImportReportController::class)->name('imports.report');

    Route::resource('imports', ImportController::class)
        ->parameters(['imports' => 'dataImport'])
        ->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::get('/leads/operation-complete', [LeadController::class, 'mutationComplete'])
        ->name('leads.mutation-complete');
    Route::resource('leads', LeadController::class);

    Route::get(
        '/opportunities/operation-complete',
        [OpportunityController::class, 'mutationComplete'],
    )->name('opportunities.mutation-complete');

    Route::patch(
        '/opportunities/{opportunity}/stage',
        [OpportunityController::class, 'moveStage'],
    )->name('opportunities.move-stage');

    Route::resource('opportunities', OpportunityController::class);

    Route::get(
        '/pipeline',
        [OpportunityController::class, 'kanban'],
    )->name('roadmap.pipeline');

    Route::get(
        '/activities/operation-complete',
        [ActivityController::class, 'mutationComplete'],
    )->name('activities.mutation-complete');

    Route::resource('activities', ActivityController::class);
    Route::get(
        '/tasks/operation-complete',
        [TaskController::class, 'mutationComplete'],
    )->name('tasks.mutation-complete');

    Route::post(
        '/tasks/{task}/complete-follow-up',
        [TaskController::class, 'completeFollowUp'],
    )->name('tasks.complete-follow-up');

    Route::resource('tasks', TaskController::class);

    Route::get(
        '/timeline',
        [TimelineController::class, 'index'],
    )->name('timeline.index');

    Route::resource('campaigns', CampaignController::class);
    Route::post('/campaigns/{campaign}/leads', [CampaignController::class, 'associateLead'])
        ->name('campaigns.leads.store');

    Route::view('/reports', 'modules.coming-soon', [
        'module' => 'Relatórios',
        'sprint' => 'Fase Analytics',
        'description' => 'Indicadores comerciais e futura integração com Grafana.',
        'items' => ['Funil', 'Conversão', 'Origem dos Leads', 'Performance Comercial', 'Tempo por Etapa', 'Grafana'],
    ])->name('roadmap.reports');

    Route::get('/companies/operation-complete', [CompanyController::class, 'mutationComplete'])->name('companies.mutation-complete');
    Route::resource('companies', CompanyController::class);
    Route::get('/contacts/operation-complete', [ContactController::class, 'mutationComplete'])->name('contacts.mutation-complete');
    Route::resource('contacts', ContactController::class);

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::resource('roles', RoleController::class)->except(['show', 'destroy']);
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
});

<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
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
});

Route::middleware(['auth', EnsureUserIsActive::class])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

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

    Route::view('/campaigns', 'modules.coming-soon', [
        'module' => 'Campanhas',
        'sprint' => 'Fase de Aquisição',
        'description' => 'Organização de campanhas, canais, UTMs e atribuição de origem.',
        'items' => ['Campanhas', 'UTM Source', 'UTM Medium', 'UTM Campaign', 'Canais', 'Conversão'],
    ])->name('roadmap.campaigns');

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

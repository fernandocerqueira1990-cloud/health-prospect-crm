<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
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

    Route::get('/leads/operation-complete', [LeadController::class, 'mutationComplete'])
        ->name('leads.mutation-complete');
    Route::resource('leads', LeadController::class);

    Route::view('/pipeline', 'modules.coming-soon', [
        'module' => 'Pipeline',
        'sprint' => 'Sprint 5',
        'description' => 'Oportunidades, estágios comerciais, histórico e visualização Kanban.',
        'items' => ['Pipelines', 'Stages', 'Opportunities', 'Stage History', 'Kanban', 'Loss Reasons'],
    ])->name('roadmap.pipeline');

    Route::view('/activities', 'modules.coming-soon', [
        'module' => 'Atividades',
        'sprint' => 'Sprint 6',
        'description' => 'Registro das interações comerciais e linha do tempo do relacionamento.',
        'items' => ['Ligações', 'E-mails', 'WhatsApp', 'Reuniões', 'Follow-ups', 'Timeline'],
    ])->name('roadmap.activities');

    Route::view('/tasks', 'modules.coming-soon', [
        'module' => 'Tarefas',
        'sprint' => 'Sprint 6',
        'description' => 'Organização de pendências, responsáveis, prazos e próximas ações.',
        'items' => ['Tarefas', 'Responsáveis', 'Prioridades', 'Prazos', 'Lembretes', 'Conclusão'],
    ])->name('roadmap.tasks');

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

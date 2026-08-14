<?php

namespace Tests\Feature\Pipeline;

use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Role;
use App\Models\Stage;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PipelineSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineKanbanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_pipeline_kanban_with_all_stages(): void
    {
        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            PipelineSeeder::class,
            StageSeeder::class,
        ]);

        $admin = User::factory()->create();

        $admin->roles()->attach(
            Role::query()
                ->where('slug', 'admin')
                ->firstOrFail(),
        );

        $pipeline = Pipeline::query()
            ->where('slug', 'comercial')
            ->firstOrFail();

        $stage = Stage::query()
            ->where('pipeline_id', $pipeline->id)
            ->where('slug', 'novo')
            ->firstOrFail();

        Opportunity::factory()->create([
            'title' => 'Projeto Kanban HIS',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('roadmap.pipeline'));

        $response
            ->assertOk()
            ->assertSee('Pipeline Comercial')
            ->assertSee('Projeto Kanban HIS')
            ->assertSee('Novo')
            ->assertSee('Qualificação')
            ->assertSee('Diagnóstico')
            ->assertSee('Demonstração')
            ->assertSee('Proposta')
            ->assertSee('Negociação')
            ->assertSee('Ganho')
            ->assertSee('Perdido');
    }
}

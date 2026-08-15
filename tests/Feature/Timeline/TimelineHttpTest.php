<?php

namespace Tests\Feature\Timeline;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $this->admin = User::factory()->create();

        $this->admin->roles()->attach(
            Role::query()
                ->where('slug', 'admin')
                ->firstOrFail(),
        );
    }

    public function test_admin_can_open_commercial_timeline(): void
    {
        $company = Company::factory()->create();

        Activity::factory()->create([
            'subject' => 'Contato comercial via WhatsApp',
            'company_id' => $company->id,
            'lead_id' => null,
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->get(route('timeline.index'));

        $response
            ->assertOk()
            ->assertSee('Timeline Comercial')
            ->assertSee('Histórico comercial')
            ->assertSee('Contato comercial via WhatsApp');
    }

    public function test_timeline_http_filters_by_company(): void
    {
        $companyA = Company::factory()->create([
            'trade_name' => 'Clínica Alpha',
        ]);

        $companyB = Company::factory()->create([
            'trade_name' => 'Clínica Beta',
        ]);

        Activity::factory()->create([
            'subject' => 'Contato Alpha',
            'company_id' => $companyA->id,
            'lead_id' => null,
        ]);

        Activity::factory()->create([
            'subject' => 'Contato Beta',
            'company_id' => $companyB->id,
            'lead_id' => null,
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->get(route('timeline.index', [
                'company_id' => $companyA->id,
            ]));

        $response
            ->assertOk()
            ->assertSee('Contato Alpha')
            ->assertDontSee('Contato Beta');
    }

    public function test_user_without_timeline_permissions_is_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('timeline.index'));

        $response->assertForbidden();
    }
}

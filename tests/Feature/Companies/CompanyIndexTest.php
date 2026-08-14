<?php

namespace Tests\Feature\Companies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class CompanyIndexTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_general_search_finds_expected_company_fields(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create([
            'legal_name' => 'Hospital Aurora', 'trade_name' => 'Rede Boreal', 'tax_id_country' => 'BR', 'tax_id' => '11444777000161',
            'email' => 'contato@aurora.test', 'phone' => '+55 11 3456-7890', 'city' => 'Campinas', 'district' => 'Cambuí',
        ]);
        Company::factory()->create(['legal_name' => 'Empresa Sem Relação']);

        foreach (['Aurora', 'Boreal', '11.444.777/0001-61', 'contato@aurora', '3456-7890', 'Campinas', 'Cambuí'] as $search) {
            $this->actingAs($admin)->get(route('companies.index', ['search' => $search]))->assertOk()->assertSee($company->legal_name)->assertDontSee('Empresa Sem Relação');
        }
    }

    public function test_specific_filters_include_location_assignee_and_priority(): void
    {
        $admin = $this->admin();
        $assigned = User::factory()->create(['name' => 'Responsável Comercial']);
        $match = Company::factory()->create([
            'legal_name' => 'Razão Filtrada', 'trade_name' => 'Fantasia Filtrada', 'segment' => 'Diagnóstico',
            'category' => 'Laboratório', 'city' => 'Santos', 'state' => 'SP', 'district' => 'Gonzaga',
            'assigned_user_id' => $assigned->id, 'priority' => 'critical',
        ]);
        Company::factory()->create(['legal_name' => 'Outra Empresa']);

        $filters = ['legal_name' => 'Razão', 'trade_name' => 'Fantasia', 'segment' => 'Diagnóstico', 'category' => 'Laboratório', 'city' => 'Santos', 'state' => 'SP', 'district' => 'Gonzaga', 'assigned_user' => $assigned->id, 'priority' => 'critical'];
        $this->actingAs($admin)->get(route('companies.index', $filters))->assertOk()->assertSee($match->legal_name)->assertDontSee('Outra Empresa');
    }

    public function test_sort_whitelist_works_and_arbitrary_column_is_ignored(): void
    {
        $admin = $this->admin();
        Company::factory()->create(['legal_name' => 'Zulu Empresa']);
        Company::factory()->create(['legal_name' => 'Alpha Empresa']);

        $response = $this->actingAs($admin)->get(route('companies.index', ['sort' => 'legal_name', 'direction' => 'asc']));
        $response->assertOk();
        $this->assertStringContainsString('Alpha Empresa', $response->getContent());
        $this->assertTrue(strpos($response->getContent(), 'Alpha Empresa') < strpos($response->getContent(), 'Zulu Empresa'));

        $this->actingAs($admin)->get(route('companies.index', ['sort' => 'legal_name; DROP TABLE companies', 'direction' => 'sideways']))
            ->assertRedirect()
            ->assertSessionHasErrors(['sort', 'direction']);
        $this->assertDatabaseCount('companies', 2);
    }

    public function test_valid_creation_date_filters_work_individually_and_as_a_range(): void
    {
        $admin = $this->admin();
        $older = Company::factory()->create(['legal_name' => 'Empresa Antiga', 'created_at' => Carbon::parse('2026-01-10 12:00:00')]);
        $newer = Company::factory()->create(['legal_name' => 'Empresa Nova', 'created_at' => Carbon::parse('2026-02-20 12:00:00')]);

        $this->actingAs($admin)->get(route('companies.index', ['created_from' => '2026-02-01']))
            ->assertOk()->assertSee($newer->legal_name)->assertDontSee($older->legal_name);
        $this->actingAs($admin)->get(route('companies.index', ['created_to' => '2026-01-31']))
            ->assertOk()->assertSee($older->legal_name)->assertDontSee($newer->legal_name);
        $this->actingAs($admin)->get(route('companies.index', ['created_from' => '2026-01-01', 'created_to' => '2026-01-31']))
            ->assertOk()->assertSee($older->legal_name)->assertDontSee($newer->legal_name);
    }

    public function test_invalid_creation_dates_are_rejected_before_query_execution(): void
    {
        $admin = $this->admin();
        Company::factory()->create();

        $this->actingAs($admin)->get(route('companies.index', ['created_from' => 'invalid']))
            ->assertRedirect()->assertSessionHasErrors('created_from');
        $this->actingAs($admin)->get(route('companies.index', ['created_to' => 'banana']))
            ->assertRedirect()->assertSessionHasErrors('created_to');
        $this->actingAs($admin)->get(route('companies.index', ['created_from' => '2026-08-10', 'created_to' => '2026-08-09']))
            ->assertRedirect()->assertSessionHasErrors('created_to');

        $this->assertDatabaseCount('companies', 1);
    }

    public function test_index_is_paginated_and_preserves_filters(): void
    {
        Company::factory()->count(16)->create(['city' => 'Recife']);

        $this->actingAs($this->admin())->get(route('companies.index', ['city' => 'Recife', 'created_from' => now()->subDay()->toDateString()]))
            ->assertOk()->assertSee('page=2')->assertSee('city=Recife')->assertSee('created_from');
    }
}

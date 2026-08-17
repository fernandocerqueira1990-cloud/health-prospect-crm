<?php

namespace Tests\Feature\Companies;

use App\Models\Company;
use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_be_created_and_assigned_to_a_user(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['legal_name' => 'Empresa Modelo', 'assigned_user_id' => $user->id]);

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'legal_name' => 'Empresa Modelo']);
        $this->assertTrue($company->assignedUser->is($user));
        $this->assertTrue($user->assignedCompanies->contains($company));
    }

    public function test_tax_id_is_unique_per_country_and_allows_multiple_nulls(): void
    {
        Company::factory()->count(2)->create(['tax_id' => null]);
        Company::factory()->create(['tax_id_country' => 'BR', 'tax_id' => '11444777000161']);
        Company::factory()->create(['tax_id_country' => 'CL', 'tax_id' => '11444777000161']);

        $this->expectException(QueryException::class);
        Company::factory()->create(['tax_id_country' => 'BR', 'tax_id' => '11444777000161']);
    }

    public function test_company_uses_soft_deletes(): void
    {
        $company = Company::factory()->create();
        $company->delete();

        $this->assertSoftDeleted($company);
        $this->assertNull(Company::find($company->id));
    }

    public function test_company_accepts_valid_lead_source_assignment(): void
    {
        $source = LeadSource::factory()->create();

        $company = Company::create([
            'legal_name' => 'Segura',
            'source_id' => $source->id,
        ]);

        $this->assertSame($source->id, $company->source_id);
        $this->assertTrue($company->source->is($source));
    }

    public function test_mass_assignment_does_not_accept_unlisted_attributes(): void
    {
        $company = Company::create([
            'legal_name' => 'Segura',
            'deleted_at' => now(),
        ]);

        $this->assertNull($company->deleted_at);
    }
}

<?php

namespace Tests\Feature\Companies;

use App\Actions\Companies\UpdateCompanyAction;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_administrator_can_complete_company_crud_with_soft_delete(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('companies.index'))->assertOk();
        $this->actingAs($admin)->post(route('companies.store'), $this->validData([
            'legal_name' => '  Hospital Exemplo S.A.  ',
            'trade_name' => '  Hospital Exemplo  ',
            'tax_id_country' => ' br ',
            'tax_id' => '11.444.777/0001-61',
            'email' => '  CONTATO@EXAMPLE.COM ',
            'website' => 'Example.COM/contato',
        ]))->assertRedirect();

        $company = Company::where('legal_name', 'Hospital Exemplo S.A.')->firstOrFail();
        $this->assertSame('Hospital Exemplo', $company->trade_name);
        $this->assertSame('BR', $company->tax_id_country);
        $this->assertSame('11444777000161', $company->tax_id);
        $this->assertSame('contato@example.com', $company->email);
        $this->assertSame('https://example.com/contato', $company->website);

        $this->actingAs($admin)->get(route('companies.show', $company))->assertOk()->assertSee('Hospital Exemplo S.A.');
        $this->actingAs($admin)->put(route('companies.update', $company), $this->validData(['legal_name' => 'Hospital Atualizado']))->assertRedirect(route('companies.show', $company));
        $this->assertSame('Hospital Atualizado', $company->refresh()->legal_name);

        $this->actingAs($admin)->delete(route('companies.destroy', $company))->assertRedirect(route('companies.index'));
        $this->assertSoftDeleted($company);
    }

    public function test_required_and_structured_fields_are_validated(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('companies.store'), $this->validData(['legal_name' => '']))->assertSessionHasErrors('legal_name');
        $this->actingAs($admin)->post(route('companies.store'), $this->validData(['tax_id_country' => 'BR', 'tax_id' => '11.111.111/1111-11']))->assertSessionHasErrors('tax_id');
        $this->actingAs($admin)->post(route('companies.store'), $this->validData(['website' => 'http://invalid host']))->assertSessionHasErrors('website');
    }

    public function test_valid_cnpj_is_accepted_and_duplicate_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('companies.store'), $this->validData(['tax_id_country' => 'BR', 'tax_id' => '11.444.777/0001-61']))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('companies.store'), $this->validData(['legal_name' => 'Duplicada', 'tax_id_country' => 'BR', 'tax_id' => '11444777000161']))->assertSessionHasErrors('tax_id');
    }

    public function test_valid_unmasked_brazilian_cnpj_is_accepted(): void
    {
        $this->actingAs($this->admin())->post(route('companies.store'), $this->validData([
            'tax_id_country' => 'BR',
            'tax_id' => '11444777000161',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('companies', ['tax_id_country' => 'BR', 'tax_id' => '11444777000161']);
    }

    public function test_fourteen_digit_and_alphanumeric_international_tax_ids_are_accepted_without_cnpj_validation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('companies.store'), $this->validData([
            'legal_name' => 'Empresa Chilena', 'tax_id_country' => 'CL', 'tax_id' => '11111111111111',
        ]))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('companies.store'), $this->validData([
            'legal_name' => 'Empresa Americana', 'tax_id_country' => 'US', 'tax_id' => ' AB-12.34/XYZ ',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('companies', ['tax_id_country' => 'CL', 'tax_id' => '11111111111111']);
        $this->assertDatabaseHas('companies', ['tax_id_country' => 'US', 'tax_id' => 'AB-12.34/XYZ']);
    }

    public function test_tax_id_country_must_be_valid_and_is_required_with_tax_id(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('companies.store'), $this->validData([
            'tax_id_country' => 'BRA', 'tax_id' => '11444777000161',
        ]))->assertSessionHasErrors('tax_id_country');
        $this->actingAs($admin)->post(route('companies.store'), $this->validData([
            'tax_id_country' => '', 'tax_id' => 'INTERNATIONAL-123',
        ]))->assertSessionHasErrors('tax_id_country');
    }

    public function test_null_tax_id_allows_null_country(): void
    {
        $this->actingAs($this->admin())->post(route('companies.store'), $this->validData())
            ->assertSessionHasNoErrors();

        $company = Company::latest('id')->firstOrFail();
        $this->assertNull($company->tax_id);
        $this->assertNull($company->tax_id_country);
    }

    public function test_same_tax_id_is_allowed_in_different_countries_but_not_in_same_country(): void
    {
        $admin = $this->admin();
        $taxId = '12345678901234';

        $this->actingAs($admin)->post(route('companies.store'), $this->validData([
            'legal_name' => 'Chile Um', 'tax_id_country' => 'CL', 'tax_id' => $taxId,
        ]))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('companies.store'), $this->validData([
            'legal_name' => 'Argentina Um', 'tax_id_country' => 'AR', 'tax_id' => $taxId,
        ]))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('companies.store'), $this->validData([
            'legal_name' => 'Chile Dois', 'tax_id_country' => 'CL', 'tax_id' => $taxId,
        ]))->assertSessionHasErrors('tax_id');
    }

    public function test_legacy_company_without_tax_country_can_be_edited_if_tax_id_is_unchanged(): void
    {
        $company = Company::factory()->create(['tax_id_country' => null, 'tax_id' => 'LEGACY-123']);

        $this->actingAs($this->admin())->put(route('companies.update', $company), $this->validData([
            'legal_name' => 'Legado atualizado', 'tax_id_country' => '', 'tax_id' => 'LEGACY-123',
        ]))->assertSessionHasNoErrors();

        $company->refresh();
        $this->assertSame('Legado atualizado', $company->legal_name);
        $this->assertNull($company->tax_id_country);
        $this->assertSame('LEGACY-123', $company->tax_id);
    }

    public function test_audit_records_tax_country_change(): void
    {
        $company = Company::factory()->create(['tax_id_country' => 'CL', 'tax_id' => '12345678901234']);

        $this->actingAs($this->admin())->put(route('companies.update', $company), $this->validData([
            'tax_id_country' => 'AR', 'tax_id' => '12345678901234',
        ]))->assertSessionHasNoErrors();

        $audit = AuditLog::where('action', 'company_updated')->latest('id')->firstOrFail();
        $this->assertSame('CL', $audit->before['tax_id_country']);
        $this->assertSame('AR', $audit->after['tax_id_country']);
    }

    public function test_inactive_user_cannot_be_assigned(): void
    {
        $inactive = User::factory()->create(['active' => false]);

        $this->actingAs($this->admin())->post(route('companies.store'), $this->validData(['assigned_user_id' => $inactive->id]))->assertSessionHasErrors('assigned_user_id');
    }

    public function test_active_user_can_be_assigned_when_company_is_created(): void
    {
        $active = User::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('companies.store'), $this->validData(['assigned_user_id' => $active->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('companies', ['assigned_user_id' => $active->id]);
    }

    public function test_current_assignee_can_be_preserved_after_becoming_inactive(): void
    {
        $assignee = User::factory()->create(['name' => 'João Silva']);
        User::factory()->create(['name' => 'Outro Inativo', 'active' => false]);
        $company = Company::factory()->create(['assigned_user_id' => $assignee->id]);
        $assignee->update(['active' => false]);

        $this->actingAs($this->admin())->get(route('companies.edit', $company))
            ->assertOk()
            ->assertSee('João Silva — Inativo')
            ->assertDontSee('Outro Inativo');

        $this->actingAs($this->admin())
            ->put(route('companies.update', $company), $this->validData([
                'legal_name' => 'Nome atualizado',
                'assigned_user_id' => $assignee->id,
            ]))
            ->assertRedirect(route('companies.show', $company));

        $this->assertSame($assignee->id, $company->refresh()->assigned_user_id);
        $audit = AuditLog::where('action', 'company_updated')->latest('id')->firstOrFail();
        $this->assertSame($assignee->id, $audit->after['assigned_user_id']);
    }

    public function test_current_inactive_assignee_can_be_removed_or_replaced_by_active_user(): void
    {
        $inactive = User::factory()->create(['active' => false]);
        $active = User::factory()->create();
        $company = Company::factory()->create(['assigned_user_id' => $inactive->id]);

        $this->actingAs($this->admin())
            ->put(route('companies.update', $company), $this->validData(['assigned_user_id' => '']))
            ->assertSessionHasNoErrors();
        $this->assertNull($company->refresh()->assigned_user_id);

        $company->update(['assigned_user_id' => $inactive->id]);
        $this->actingAs($this->admin())
            ->put(route('companies.update', $company), $this->validData(['assigned_user_id' => $active->id]))
            ->assertSessionHasNoErrors();
        $this->assertSame($active->id, $company->refresh()->assigned_user_id);
    }

    public function test_current_inactive_assignee_cannot_be_replaced_by_another_inactive_user_over_http(): void
    {
        $current = User::factory()->create(['active' => false]);
        $otherInactive = User::factory()->create(['active' => false]);
        $company = Company::factory()->create(['assigned_user_id' => $current->id]);

        $this->actingAs($this->admin())
            ->put(route('companies.update', $company), $this->validData(['assigned_user_id' => $otherInactive->id]))
            ->assertSessionHasErrors('assigned_user_id');

        $this->assertSame($current->id, $company->refresh()->assigned_user_id);
    }

    public function test_action_rejects_inactive_assignee_if_http_validation_is_bypassed(): void
    {
        $inactive = User::factory()->create(['active' => false]);
        $company = Company::factory()->create();

        try {
            app(UpdateCompanyAction::class)->execute($company, [
                'legal_name' => $company->legal_name,
                'assigned_user_id' => $inactive->id,
            ]);
            $this->fail('A atribuição direta de usuário inativo deveria falhar.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('assigned_user_id', $exception->errors());
        } catch (QueryException $exception) {
            $this->fail('A regra de domínio deve falhar antes da foreign key: '.$exception->getMessage());
        }

        $this->assertNull($company->refresh()->assigned_user_id);
    }

    public function test_each_company_permission_is_enforced_and_missing_permission_returns_403(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($this->userWithPermission('companies.view'))->get(route('companies.index'))->assertOk();
        $this->actingAs($this->userWithPermission('companies.create'))->post(route('companies.store'), $this->validData())->assertRedirect();
        $this->actingAs($this->userWithPermission('companies.update'))->put(route('companies.update', $company), $this->validData(['legal_name' => 'Permitida']))->assertRedirect();
        $this->actingAs($this->userWithPermission('companies.delete'))->delete(route('companies.destroy', $company))->assertRedirect();

        $unauthorized = User::factory()->create();
        $other = Company::factory()->create();
        $this->actingAs($unauthorized)->get(route('companies.index'))->assertForbidden();
        $this->actingAs($unauthorized)->get(route('companies.show', $other))->assertForbidden();
        $this->actingAs($unauthorized)->post(route('companies.store'), $this->validData())->assertForbidden();
        $this->actingAs($unauthorized)->put(route('companies.update', $other), $this->validData())->assertForbidden();
        $this->actingAs($unauthorized)->delete(route('companies.destroy', $other))->assertForbidden();
        $this->assertDatabaseHas('companies', ['id' => $other->id, 'deleted_at' => null]);
    }

    public function test_user_with_create_and_view_is_redirected_to_created_company(): void
    {
        $user = $this->userWithPermission('companies.create');
        $user->roles()->firstOrFail()->permissions()->attach(Permission::where('slug', 'companies.view')->firstOrFail());

        $response = $this->actingAs($user)->post(route('companies.store'), $this->validData(['legal_name' => 'Empresa Visível']));
        $company = Company::where('legal_name', 'Empresa Visível')->firstOrFail();

        $response->assertRedirect(route('companies.show', $company))->assertSessionHas('status');
        $this->actingAs($user)->get(route('companies.show', $company))->assertOk();
    }

    public function test_create_only_user_is_redirected_to_authorized_confirmation(): void
    {
        $user = $this->userWithPermission('companies.create');

        $response = $this->actingAs($user)->post(route('companies.store'), $this->validData(['legal_name' => 'Empresa Criada']));

        $response->assertRedirect(route('companies.mutation-complete'))->assertSessionHas('status');
        $this->actingAs($user)->get(route('companies.mutation-complete'))->assertOk()->assertSee('Operação concluída');
        $this->assertDatabaseHas('companies', ['legal_name' => 'Empresa Criada']);
    }

    public function test_update_only_user_persists_change_and_reaches_authorized_confirmation(): void
    {
        $user = $this->userWithPermission('companies.update');
        $company = Company::factory()->create();

        $response = $this->actingAs($user)->put(route('companies.update', $company), $this->validData(['legal_name' => 'Atualização sem view']));

        $response->assertRedirect(route('companies.mutation-complete'))->assertSessionHas('status');
        $this->actingAs($user)->get(route('companies.mutation-complete'))->assertOk();
        $this->assertSame('Atualização sem view', $company->refresh()->legal_name);
    }

    public function test_delete_only_user_soft_deletes_and_reaches_authorized_confirmation(): void
    {
        $user = $this->userWithPermission('companies.delete');
        $company = Company::factory()->create();

        $response = $this->actingAs($user)->delete(route('companies.destroy', $company));

        $response->assertRedirect(route('companies.mutation-complete'))->assertSessionHas('status');
        $this->actingAs($user)->get(route('companies.mutation-complete'))->assertOk();
        $this->assertSoftDeleted($company);
    }

    public function test_users_without_mutation_permissions_remain_forbidden(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('companies.store'), $this->validData())->assertForbidden();
        $this->actingAs($user)->put(route('companies.update', $company), $this->validData())->assertForbidden();
        $this->actingAs($user)->delete(route('companies.destroy', $company))->assertForbidden();
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'deleted_at' => null]);
    }

    public function test_show_and_index_stay_protected_while_admin_flow_stays_unchanged(): void
    {
        $company = Company::factory()->create();
        $withoutView = $this->userWithPermission('companies.create');

        $this->actingAs($withoutView)->get(route('companies.index'))->assertForbidden();
        $this->actingAs($withoutView)->get(route('companies.show', $company))->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('companies.index'))->assertOk();
        $this->actingAs($admin)->get(route('companies.show', $company))->assertOk();
        $this->actingAs($admin)->post(route('companies.store'), $this->validData(['legal_name' => 'Empresa Admin']))
            ->assertRedirect(route('companies.show', Company::where('legal_name', 'Empresa Admin')->firstOrFail()));
    }

    public function test_audit_records_create_update_and_delete_with_sanitized_snapshots(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('companies.store'), $this->validData(['priority' => 'high']));
        $company = Company::latest('id')->firstOrFail();
        $created = AuditLog::where('action', 'company_created')->firstOrFail();
        $this->assertSame('high', $created->after['priority']);

        $this->actingAs($admin)->put(route('companies.update', $company), $this->validData(['priority' => 'critical', 'assigned_user_id' => $admin->id]));
        $updated = AuditLog::where('action', 'company_updated')->firstOrFail();
        $this->assertSame('high', $updated->before['priority']);
        $this->assertSame('critical', $updated->after['priority']);
        $this->assertSame($admin->id, $updated->after['assigned_user_id']);

        $this->actingAs($admin)->delete(route('companies.destroy', $company));
        $deleted = AuditLog::where('action', 'company_deleted')->firstOrFail();
        $this->assertSame('critical', $deleted->before['priority']);
        $this->assertNotNull($deleted->after['deleted_at']);
        $this->assertArrayNotHasKey('password', $deleted->after);
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'legal_name' => 'Clínica Modelo Ltda.',
            'trade_name' => '', 'tax_id_country' => '', 'tax_id' => '', 'segment' => 'Saúde', 'category' => 'Clínica',
            'website' => '', 'phone' => '+55 11 99999-9999', 'email' => '', 'street' => '',
            'number' => '', 'complement' => '', 'district' => '', 'city' => 'São Paulo', 'state' => 'sp',
            'postal_code' => '', 'employee_count_estimate' => '', 'assigned_user_id' => '',
            'priority' => 'medium', 'notes' => '',
        ], $overrides);
    }
}

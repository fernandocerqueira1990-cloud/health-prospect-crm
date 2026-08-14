<?php

namespace Tests\Feature\Contacts;

use App\Actions\Contacts\LockContactCompaniesAction;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class ContactManagementTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_relationships_soft_delete_and_company_soft_delete_preserves_contacts(): void
    {
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $this->assertTrue($contact->company->is($company));
        $this->assertTrue($company->contacts->contains($contact));
        $company->delete();
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'deleted_at' => null]);
        $this->assertTrue($contact->fresh()->company->is($company));
        $this->assertTrue($contact->fresh()->company->trashed());
        $contact->delete();
        $this->assertSoftDeleted($contact);
    }

    public function test_archived_company_contact_remains_visible_searchable_and_clearly_labelled(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create(['legal_name' => 'Empresa Histórica Única']);
        $contact = Contact::factory()->for($company)->create(['name' => 'Contato Preservado']);
        $company->delete();

        $this->actingAs($admin)->get(route('contacts.show', $contact))
            ->assertOk()->assertSee('Empresa Histórica Única')->assertSee('Arquivada')
            ->assertDontSee('href="'.route('companies.show', $company).'"', false);
        $this->actingAs($admin)->get(route('contacts.index'))
            ->assertOk()->assertSee('Contato Preservado')->assertSee('Empresa arquivada');
        $this->actingAs($admin)->get(route('contacts.index', ['search' => 'Empresa Histórica Única']))
            ->assertOk()->assertSee('Contato Preservado');
        $this->actingAs($admin)->get(route('contacts.edit', $contact))
            ->assertOk()->assertSee('Empresa Histórica Única — Arquivada');
    }

    public function test_archived_company_rules_allow_current_history_but_reject_new_links(): void
    {
        $admin = $this->admin();
        $archivedCurrent = Company::factory()->create();
        $otherArchived = Company::factory()->create();
        $active = Company::factory()->create();
        $contact = Contact::factory()->for($archivedCurrent)->create(['name' => 'Nome anterior']);
        $archivedCurrent->delete();
        $otherArchived->delete();

        $this->actingAs($admin)->post(route('contacts.store'), $this->data($otherArchived))
            ->assertInvalid(['company_id']);
        $this->actingAs($admin)->put(route('contacts.update', $contact), $this->data($archivedCurrent, ['name' => 'Histórico editado']))
            ->assertRedirect();
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'company_id' => $archivedCurrent->id, 'name' => 'Histórico editado']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'contact_updated', 'auditable_id' => $contact->id]);

        $this->actingAs($admin)->put(route('contacts.update', $contact), $this->data($otherArchived))
            ->assertInvalid(['company_id']);
        $this->actingAs($admin)->put(route('contacts.update', $contact), $this->data($active, ['name' => 'Movido para ativa']))
            ->assertRedirect();
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'company_id' => $active->id, 'name' => 'Movido para ativa']);
    }

    public function test_moving_primary_contact_to_company_with_primary_preserves_invariants(): void
    {
        $admin = $this->admin();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $moving = Contact::factory()->for($companyA)->create(['is_primary' => true]);
        $previousB = Contact::factory()->for($companyB)->create(['is_primary' => true]);

        $this->actingAs($admin)->put(route('contacts.update', $moving), $this->data($companyB, ['is_primary' => '1']))
            ->assertRedirect();

        $this->assertSame($companyB->id, $moving->refresh()->company_id);
        $this->assertTrue($moving->is_primary);
        $this->assertFalse($previousB->refresh()->is_primary);
        $this->assertSame(0, Contact::where('company_id', $companyA->id)->where('is_primary', true)->count());
        $this->assertSame(1, Contact::where('company_id', $companyB->id)->where('is_primary', true)->count());
    }

    public function test_company_locks_are_acquired_in_deterministic_ascending_order(): void
    {
        $first = Company::factory()->create();
        $second = Company::factory()->create();

        $locked = app(LockContactCompaniesAction::class)->execute([$second->id, $first->id]);

        $this->assertSame([$first->id, $second->id], $locked->modelKeys());
    }

    public function test_contact_actions_follow_company_before_contact_lock_policy(): void
    {
        foreach (['UpdateContactAction.php', 'DeleteContactAction.php'] as $file) {
            $source = file_get_contents(app_path('Actions/Contacts/'.$file));
            $this->assertIsString($source);
            $companyLock = strpos($source, '$this->lockCompanies->execute');
            $contactLock = strpos($source, 'Contact::query()->lockForUpdate()');
            $this->assertIsInt($companyLock);
            $this->assertIsInt($contactLock);
            $this->assertLessThan($contactLock, $companyLock, $file.' deve bloquear Company antes de Contact.');
        }
    }

    public function test_primary_invariant_moves_primary_and_delete_leaves_none(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $first = $this->createViaHttp($admin, $company, ['name' => 'Primeiro', 'is_primary' => '1']);
        $second = $this->createViaHttp($admin, $company, ['name' => 'Segundo', 'is_primary' => '1']);
        $this->assertFalse($first->refresh()->is_primary);
        $this->assertTrue($second->refresh()->is_primary);
        $this->actingAs($admin)->delete(route('contacts.destroy', $second))->assertRedirect();
        $this->assertSame(0, Contact::where('company_id', $company->id)->where('is_primary', true)->count());
    }

    public function test_database_rejects_two_active_primary_contacts_but_allows_one_per_company(): void
    {
        $company = Company::factory()->create();
        Contact::factory()->for($company)->create(['is_primary' => true]);
        Contact::factory()->for(Company::factory())->create(['is_primary' => true]);
        $this->expectException(QueryException::class);
        Contact::factory()->for($company)->create(['is_primary' => true]);
    }

    public function test_validation_and_normalization(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $this->actingAs($admin)->post(route('contacts.store'), $this->data($company, [
            'name' => '  Maria Silva  ', 'email' => '  MARIA@EXAMPLE.COM ', 'phone' => '+55 (11) 99999-0000',
        ]))->assertRedirect();
        $this->assertDatabaseHas('contacts', ['name' => 'Maria Silva', 'email' => 'maria@example.com', 'phone' => '+5511999990000']);

        $this->actingAs($admin)->post(route('contacts.store'), $this->data($company, [
            'name' => '', 'company_id' => 999999, 'email' => 'invalid', 'linkedin_url' => 'https://example.com/profile',
            'decision_role' => 'boss', 'influence_level' => 'extreme',
        ]))->assertInvalid(['name', 'company_id', 'email', 'linkedin_url', 'decision_role', 'influence_level']);
    }

    public function test_rbac_protects_every_crud_endpoint_and_admin_can_use_them(): void
    {
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $unauthorized = User::factory()->create();
        $this->actingAs($unauthorized)->get(route('contacts.index'))->assertForbidden();
        $this->actingAs($unauthorized)->get(route('contacts.show', $contact))->assertForbidden();
        $this->actingAs($unauthorized)->post(route('contacts.store'), $this->data($company))->assertForbidden();
        $this->actingAs($unauthorized)->put(route('contacts.update', $contact), $this->data($company))->assertForbidden();
        $this->actingAs($unauthorized)->delete(route('contacts.destroy', $contact))->assertForbidden();

        $this->actingAs($this->userWithPermission('contacts.view'))->get(route('contacts.index'))->assertOk();
        $this->actingAs($this->userWithPermission('contacts.create'))->post(route('contacts.store'), $this->data($company))->assertRedirect();
        $this->actingAs($this->userWithPermission('contacts.update'))->put(route('contacts.update', $contact), $this->data($company, ['name' => 'Atualizado']))->assertRedirect();
        $this->actingAs($this->userWithPermission('contacts.delete'))->delete(route('contacts.destroy', $contact))->assertRedirect();
    }

    public function test_admin_crud_and_audit_snapshots(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $contact = $this->createViaHttp($admin, $company, ['email' => ' PERSON@EXAMPLE.COM ']);
        $this->actingAs($admin)->get(route('contacts.index'))->assertOk()->assertSee($contact->name);
        $this->actingAs($admin)->get(route('contacts.show', $contact))->assertOk()->assertSee($company->legal_name);
        $this->actingAs($admin)->put(route('contacts.update', $contact), $this->data($company, ['name' => 'Nome novo', 'active' => '0']))->assertRedirect();
        $this->assertFalse($contact->refresh()->active);
        $this->assertSame('person@example.com', AuditLog::where('action', 'contact_created')->firstOrFail()->after['email']);
        $this->assertSame($contact->id, AuditLog::where('action', 'contact_updated')->firstOrFail()->auditable_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'contact_deactivated', 'auditable_id' => $contact->id]);
        $this->actingAs($admin)->delete(route('contacts.destroy', $contact))->assertRedirect();
        $this->assertSoftDeleted($contact);
        $this->assertDatabaseHas('audit_logs', ['action' => 'contact_deleted', 'auditable_id' => $contact->id]);
    }

    public function test_global_search_and_all_filters(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create(['legal_name' => 'Hospital Aurora']);
        $contact = Contact::factory()->for($company)->create(['name' => 'Carla Única', 'job_title' => 'CIO Especial', 'department' => 'Tecnologia Singular', 'email' => 'carla@aurora.test', 'phone' => '+551188887777', 'decision_role' => 'decision_maker', 'influence_level' => 'critical', 'is_primary' => true, 'active' => true]);
        foreach (['Carla Única', 'Hospital Aurora', 'CIO Especial', 'Tecnologia Singular', 'carla@aurora.test', '+551188887777'] as $search) {
            $this->actingAs($admin)->get(route('contacts.index', ['search' => $search]))->assertOk()->assertSee($contact->name);
        }
        foreach ([['company' => $company->id], ['name' => 'Carla'], ['job_title' => 'CIO'], ['department' => 'Tecnologia'], ['email' => 'aurora.test'], ['phone' => '8888'], ['decision_role' => 'decision_maker'], ['influence_level' => 'critical'], ['is_primary' => '1'], ['active' => '1']] as $filter) {
            $this->actingAs($admin)->get(route('contacts.index', $filter))->assertOk()->assertSee($contact->name);
        }
        $this->actingAs($admin)->get(route('contacts.index', ['sort' => 'password']))->assertInvalid(['sort']);
    }

    public function test_phone_filter_and_general_search_use_persistence_normalization(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create([
            'name' => 'Nome sem números',
            'email' => 'phone-search@example.test',
            'phone' => '+55 (11) 99999-0000',
            'whatsapp' => '+44 20 7946-0958',
        ]);

        $this->assertSame('+5511999990000', $contact->phone);
        $this->assertSame('+442079460958', $contact->whatsapp);
        foreach ([
            ['phone' => '+5511999990000'],
            ['phone' => '+55 (11) 99999-0000'],
            ['phone' => '11 99999-0000'],
            ['search' => '+55 (11) 99999-0000'],
            ['search' => '+44 (20) 7946-0958'],
            ['search' => 'Nome sem números'],
            ['search' => 'phone-search@example.test'],
        ] as $filter) {
            $this->actingAs($admin)->get(route('contacts.index', $filter))
                ->assertOk()->assertSee($contact->name);
        }

        $this->actingAs($admin)->get(route('contacts.index', ['phone' => '   ']))
            ->assertOk()->assertSee($contact->name);
    }

    public function test_phone_search_remains_parameterized(): void
    {
        $admin = $this->admin();
        Contact::factory()->create(['phone' => '+5511999990000']);
        $contactQuery = null;
        DB::listen(function ($query) use (&$contactQuery): void {
            if (str_contains($query->sql, 'from "contacts"') && str_contains($query->sql, '"phone"::text ilike')) {
                $contactQuery = $query;
            }
        });

        $this->actingAs($admin)->get(route('contacts.index', ['search' => '+55 (11) 99999-0000']))->assertOk();

        $this->assertNotNull($contactQuery);
        $this->assertStringContainsString('?', $contactQuery->sql);
        $this->assertContains('%+5511999990000%', $contactQuery->bindings);
    }

    public function test_company_show_uses_bounded_contacts_paginator_with_independent_page_name(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $primary = Contact::factory()->for($company)->create(['name' => 'Contato Principal', 'is_primary' => true]);
        foreach (range(1, 11) as $number) {
            Contact::factory()->for($company)->create(['name' => sprintf('Contato %02d', $number)]);
        }
        $deleted = Contact::factory()->for($company)->create(['name' => 'Contato Excluído']);
        $deleted->delete();

        $firstPage = $this->actingAs($admin)->get(route('companies.show', $company));
        $firstPage->assertOk()
            ->assertSee($primary->name)
            ->assertSee('Contatos (12)')
            ->assertSee('contacts_page=2')
            ->assertViewHas('company', fn (Company $viewCompany): bool => ! $viewCompany->relationLoaded('contacts'))
            ->assertViewHas('contacts', fn ($contacts): bool => $contacts->count() === 10
                && $contacts->total() === 12
                && $contacts->getPageName() === 'contacts_page'
                && $contacts->first()->is($primary));
        $firstPage->assertDontSee('Contato Excluído');

        $secondPage = $this->actingAs($admin)->get(route('companies.show', [$company, 'contacts_page' => 2]));
        $secondPage->assertOk()
            ->assertDontSee($primary->name)
            ->assertViewHas('contacts', fn ($contacts): bool => $contacts->currentPage() === 2
                && $contacts->count() === 2
                && $contacts->total() === 12);
    }

    public function test_company_show_with_few_contacts_and_permissions_remains_functional(): void
    {
        $company = Company::factory()->create();
        $contacts = Contact::factory()->count(3)->for($company)->create();
        $viewer = $this->userWithPermission('companies.view');

        $this->actingAs($viewer)->get(route('companies.show', $company))
            ->assertOk()->assertDontSee($contacts->first()->name);

        $admin = $this->admin();
        $response = $this->actingAs($admin)->get(route('companies.show', $company))->assertOk();
        foreach ($contacts as $contact) {
            $response->assertSee($contact->name);
        }
    }

    public function test_index_paginates_fifteen_contacts_and_preserves_filters(): void
    {
        $admin = $this->admin();
        Contact::factory()->count(16)->create(['department' => 'Paginação']);

        $response = $this->actingAs($admin)->get(route('contacts.index', ['department' => 'Paginação']));

        $response->assertOk()->assertViewHas('contacts', function ($contacts): bool {
            return $contacts->count() === 15
                && $contacts->total() === 16
                && str_contains($contacts->nextPageUrl() ?? '', 'department=Pagina%C3%A7%C3%A3o');
        });
    }

    private function createViaHttp(User $user, Company $company, array $overrides = []): Contact
    {
        $this->actingAs($user)->post(route('contacts.store'), $this->data($company, $overrides))->assertRedirect();

        return Contact::latest('id')->firstOrFail();
    }

    private function data(Company $company, array $overrides = []): array
    {
        return array_merge(['company_id' => $company->id, 'name' => 'Contato Teste', 'job_title' => 'CIO', 'department' => 'TI', 'email' => 'contato@example.com', 'phone' => '+5511999999999', 'whatsapp' => '+5511999999999', 'linkedin_url' => 'https://www.linkedin.com/in/contato', 'decision_role' => 'decision_maker', 'influence_level' => 'high', 'is_primary' => '0', 'active' => '1', 'notes' => 'Sem segredo.'], $overrides);
    }
}

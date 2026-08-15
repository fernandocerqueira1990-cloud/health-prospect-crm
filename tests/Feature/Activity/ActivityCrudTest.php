<?php

namespace Tests\Feature\Activity;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityCrudTest extends TestCase
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
            Role::query()->where('slug', 'admin')->firstOrFail(),
        );
    }

    public function test_admin_can_open_activities_index(): void
    {
        Activity::factory()->create([
            'subject' => 'Ligação com cliente',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->get(route('activities.index'));

        $response
            ->assertOk()
            ->assertSee('Atividades')
            ->assertSee('Ligação com cliente');
    }

    public function test_admin_can_create_activity(): void
    {
        $company = Company::factory()->create();

        $response = $this
            ->actingAs($this->admin)
            ->post(route('activities.store'), [
                'type' => 'whatsapp',
                'direction' => 'outbound',
                'subject' => 'Follow-up comercial',
                'description' => 'Contato realizado via WhatsApp.',
                'outcome' => 'Cliente solicitou nova conversa.',
                'company_id' => $company->id,
                'assigned_user_id' => $this->admin->id,
                'occurred_at' => now()->format('Y-m-d H:i:s'),
                'duration_minutes' => 15,
            ]);

        $activity = Activity::query()
            ->where('subject', 'Follow-up comercial')
            ->firstOrFail();

        $response->assertRedirect(
            route('activities.show', $activity),
        );

        $this->assertSame('whatsapp', $activity->type);
        $this->assertSame($company->id, $activity->company_id);
        $this->assertSame($this->admin->id, $activity->assigned_user_id);
        $this->assertSame($this->admin->id, $activity->created_by_user_id);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'activity_created',
            'auditable_id' => $activity->id,
        ]);
    }

    public function test_activity_requires_commercial_entity(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->post(route('activities.store'), [
                'type' => 'call',
                'subject' => 'Atividade sem vínculo',
                'occurred_at' => now()->format('Y-m-d H:i:s'),
            ]);

        $response->assertSessionHasErrors('company_id');

        $this->assertDatabaseMissing('activities', [
            'subject' => 'Atividade sem vínculo',
        ]);
    }

    public function test_admin_can_update_activity(): void
    {
        $activity = Activity::factory()->create([
            'subject' => 'Assunto original',
            'duration_minutes' => 10,
        ]);

        $companyId = $activity->company_id;
        $contactId = $activity->contact_id;
        $leadId = $activity->lead_id;
        $opportunityId = $activity->opportunity_id;

        $response = $this
            ->actingAs($this->admin)
            ->put(route('activities.update', $activity), [
                'type' => $activity->type,
                'direction' => $activity->direction,
                'subject' => 'Assunto atualizado',
                'description' => $activity->description,
                'outcome' => 'Cliente solicitou demonstração.',
                'company_id' => $companyId,
                'contact_id' => $contactId,
                'lead_id' => $leadId,
                'opportunity_id' => $opportunityId,
                'assigned_user_id' => $this->admin->id,
                'occurred_at' => $activity->occurred_at
                    ->format('Y-m-d H:i:s'),
                'duration_minutes' => 20,
            ]);

        $response->assertRedirect(
            route('activities.show', $activity),
        );

        $activity->refresh();

        $this->assertSame('Assunto atualizado', $activity->subject);
        $this->assertSame(20, $activity->duration_minutes);
        $this->assertSame(
            'Cliente solicitou demonstração.',
            $activity->outcome,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'activity_updated',
            'auditable_id' => $activity->id,
        ]);
    }

    public function test_admin_can_open_activity_show(): void
    {
        $activity = Activity::factory()->create([
            'subject' => 'Reunião de diagnóstico',
            'type' => 'meeting',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->get(route('activities.show', $activity));

        $response
            ->assertOk()
            ->assertSee('Reunião de diagnóstico')
            ->assertSee('Reunião')
            ->assertSee('Vínculos comerciais');
    }

    public function test_admin_can_soft_delete_activity(): void
    {
        $activity = Activity::factory()->create();

        $response = $this
            ->actingAs($this->admin)
            ->delete(route('activities.destroy', $activity));

        $response->assertRedirect(route('activities.index'));

        $this->assertSoftDeleted('activities', [
            'id' => $activity->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'activity_deleted',
            'auditable_id' => $activity->id,
        ]);
    }

    public function test_readonly_user_cannot_create_activity(): void
    {
        $readonly = User::factory()->create();

        $readonly->roles()->attach(
            Role::query()->where('slug', 'readonly')->firstOrFail(),
        );

        $company = Company::factory()->create();

        $response = $this
            ->actingAs($readonly)
            ->post(route('activities.store'), [
                'type' => 'call',
                'subject' => 'Não autorizado',
                'company_id' => $company->id,
                'occurred_at' => now()->format('Y-m-d H:i:s'),
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('activities', [
            'subject' => 'Não autorizado',
        ]);
    }
}

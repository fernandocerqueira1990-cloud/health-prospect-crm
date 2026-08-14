<?php

namespace Tests\Feature\Lead;

use App\Models\Channel;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\LeadSourceSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCrudTest extends TestCase
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
            LeadSourceSeeder::class,
            ChannelSeeder::class,
        ]);

        $this->admin = User::factory()->create();

        $this->admin->roles()->attach(
            Role::query()->where('slug', 'admin')->firstOrFail(),
        );
    }

    public function test_admin_can_open_leads_index(): void
    {
        $lead = Lead::factory()->create([
            'name' => 'Maria Souza',
            'company_name' => 'Clínica Vida',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->get(route('leads.index'));

        $response
            ->assertOk()
            ->assertSee('Leads')
            ->assertSee('Maria Souza')
            ->assertSee('Clínica Vida');
    }

    public function test_admin_can_create_lead(): void
    {
        $source = LeadSource::query()
            ->where('slug', 'prospeccao-ativa')
            ->firstOrFail();

        $channel = Channel::query()
            ->where('slug', 'linkedin')
            ->firstOrFail();

        $response = $this
            ->actingAs($this->admin)
            ->post(route('leads.store'), [
                'name' => 'Carlos Andrade',
                'company_name' => 'Hospital Horizonte',
                'job_title' => 'Gerente de TI',
                'email' => ' CARLOS@EXAMPLE.COM ',
                'phone' => '(71) 99999-1111',
                'whatsapp' => '+55 (71) 98888-2222',
                'source_id' => $source->id,
                'channel_id' => $channel->id,
                'assigned_user_id' => $this->admin->id,
                'status' => 'new',
                'priority' => 'high',
                'temperature' => 'hot',
                'score' => 80,
                'notes' => 'Lead criado pelo teste.',
            ]);

        $lead = Lead::query()
            ->where('name', 'Carlos Andrade')
            ->firstOrFail();

        $response->assertRedirect(route('leads.show', $lead));

        $this->assertSame('carlos@example.com', $lead->email);
        $this->assertSame('71999991111', $lead->phone);
        $this->assertSame('+5571988882222', $lead->whatsapp);
        $this->assertSame('high', $lead->priority);
        $this->assertSame(80, $lead->score);

        $this->assertSame(1, $lead->sourceEvents()->count());

        $event = $lead->sourceEvents()->firstOrFail();

        $this->assertSame('lead_created', $event->event_type);
        $this->assertSame('prospeccao-ativa', $event->source);
        $this->assertSame('linkedin', $event->channel);

        $lead->refresh();

        $this->assertSame(
            $event->id,
            $lead->first_touch_source_event_id,
        );

        $this->assertSame(
            $event->id,
            $lead->last_touch_source_event_id,
        );

        $this->assertSame(
            $event->id,
            $lead->firstTouchSourceEvent?->id,
        );

        $this->assertSame(
            $event->id,
            $lead->lastTouchSourceEvent?->id,
        );
    }

    public function test_contact_must_belong_to_selected_company(): void
    {
        $source = LeadSource::query()->firstOrFail();
        $channel = Channel::query()->firstOrFail();

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $contact = Contact::factory()->create([
            'company_id' => $companyB->id,
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->from(route('leads.create'))
            ->post(route('leads.store'), [
                'name' => 'Lead inválido',
                'company_id' => $companyA->id,
                'contact_id' => $contact->id,
                'source_id' => $source->id,
                'channel_id' => $channel->id,
                'status' => 'new',
                'score' => 10,
            ]);

        $response
            ->assertRedirect(route('leads.create'))
            ->assertSessionHasErrors('contact_id');

        $this->assertDatabaseMissing('leads', [
            'name' => 'Lead inválido',
        ]);
    }

    public function test_admin_can_update_lead(): void
    {
        $lead = Lead::factory()->create([
            'name' => 'Lead original',
            'status' => 'new',
            'score' => 20,
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->put(route('leads.update', $lead), [
                'name' => 'Lead atualizado',
                'source_id' => $lead->source_id,
                'channel_id' => $lead->channel_id,
                'status' => 'qualified',
                'priority' => 'high',
                'temperature' => 'hot',
                'score' => 90,
            ]);

        $response->assertRedirect(route('leads.show', $lead));

        $lead->refresh();

        $this->assertSame('Lead atualizado', $lead->name);
        $this->assertSame('qualified', $lead->status);
        $this->assertSame(90, $lead->score);
    }

    public function test_admin_can_soft_delete_lead(): void
    {
        $lead = Lead::factory()->create();

        $response = $this
            ->actingAs($this->admin)
            ->delete(route('leads.destroy', $lead));

        $response->assertRedirect(route('leads.index'));

        $this->assertSoftDeleted('leads', [
            'id' => $lead->id,
        ]);
    }

    public function test_readonly_user_cannot_create_lead(): void
    {
        $readonly = User::factory()->create();

        $readonly->roles()->attach(
            Role::query()->where('slug', 'readonly')->firstOrFail(),
        );

        $response = $this
            ->actingAs($readonly)
            ->get(route('leads.create'));

        $response->assertForbidden();
    }
}

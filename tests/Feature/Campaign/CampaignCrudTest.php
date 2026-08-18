<?php

namespace Tests\Feature\Campaign;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class CampaignCrudTest extends TestCase
{
    use InteractsWithRbac;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_authorized_user_accesses_paginated_index(): void
    {
        Campaign::factory()->count(16)->create();

        $this->actingAs($this->userWithPermission('campaigns.view'))
            ->get(route('campaigns.index'))
            ->assertOk()
            ->assertViewIs('campaigns.index')
            ->assertViewHas('campaigns', fn ($campaigns): bool => $campaigns->count() === 15 && $campaigns->total() === 16);
    }

    public function test_create_is_protected_by_policy(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('campaigns.create'))
            ->assertForbidden();

        $this->actingAs($this->userWithPermission('campaigns.create'))
            ->get(route('campaigns.create'))
            ->assertOk();
    }

    public function test_store_creates_valid_campaign_and_audit_log(): void
    {
        $channel = Channel::factory()->create();
        $owner = User::factory()->create();
        $actor = $this->userWithPermission('campaigns.create');

        $this->actingAs($actor)->post(route('campaigns.store'), $this->validPayload([
            'channel_id' => $channel->id,
            'owner_user_id' => $owner->id,
        ]))->assertRedirect(route('dashboard'));

        $campaign = Campaign::where('name', 'Campanha Agosto')->firstOrFail();
        $this->assertSame($channel->id, $campaign->channel_id);
        $this->assertSame($owner->id, $campaign->owner_user_id);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'action' => 'campaign_created',
            'auditable_type' => Campaign::class,
            'auditable_id' => $campaign->id,
        ]);
    }

    public function test_validation_rejects_invalid_payload(): void
    {
        $this->actingAs($this->userWithPermission('campaigns.create'))
            ->post(route('campaigns.store'), [
                'name' => ' ', 'status' => 'unknown', 'currency' => 'br',
            ])->assertSessionHasErrors(['name', 'status', 'currency']);

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_show_is_authorized(): void
    {
        $campaign = Campaign::factory()->create();

        $this->actingAs($this->userWithPermission('campaigns.view'))
            ->get(route('campaigns.show', $campaign))
            ->assertOk()
            ->assertSee($campaign->name);

        $this->actingAs(User::factory()->create())
            ->get(route('campaigns.show', $campaign))
            ->assertForbidden();
    }

    public function test_update_changes_campaign_and_records_audit(): void
    {
        $campaign = Campaign::factory()->create(['name' => 'Nome antigo']);
        $actor = $this->userWithPermission('campaigns.update');

        $this->actingAs($actor)
            ->put(route('campaigns.update', $campaign), $this->validPayload(['name' => 'Nome atualizado']))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'name' => 'Nome atualizado']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'campaign_updated', 'auditable_id' => $campaign->id]);
    }

    public function test_destroy_soft_deletes_campaign_and_records_audit(): void
    {
        $campaign = Campaign::factory()->create();
        $actor = $this->userWithPermission('campaigns.delete');

        $this->actingAs($actor)->delete(route('campaigns.destroy', $campaign))->assertRedirect(route('dashboard'));

        $this->assertSoftDeleted($campaign);
        $this->assertDatabaseHas('audit_logs', ['action' => 'campaign_deleted', 'auditable_id' => $campaign->id]);
    }

    public function test_user_without_permission_receives_403_for_crud_routes(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $this->actingAs($user)->get(route('campaigns.index'))->assertForbidden();
        $this->actingAs($user)->post(route('campaigns.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($user)->put(route('campaigns.update', $campaign), $this->validPayload())->assertForbidden();
        $this->actingAs($user)->delete(route('campaigns.destroy', $campaign))->assertForbidden();
    }

    public function test_admin_can_use_complete_crud_via_global_rule(): void
    {
        $admin = $this->admin();
        $campaign = Campaign::factory()->create();

        $this->actingAs($admin)->get(route('campaigns.index'))->assertOk();
        $this->actingAs($admin)->get(route('campaigns.create'))->assertOk();
        $this->actingAs($admin)->get(route('campaigns.show', $campaign))->assertOk();
        $this->actingAs($admin)->get(route('campaigns.edit', $campaign))->assertOk();
    }

    public function test_unknown_or_inactive_channel_and_owner_are_rejected_on_create(): void
    {
        $inactiveChannel = Channel::factory()->create(['active' => false]);
        $inactiveOwner = User::factory()->create(['active' => false]);

        $this->actingAs($this->userWithPermission('campaigns.create'))
            ->post(route('campaigns.store'), $this->validPayload([
                'channel_id' => $inactiveChannel->id,
                'owner_user_id' => $inactiveOwner->id,
            ]))->assertSessionHasErrors(['channel_id', 'owner_user_id']);

        $this->actingAs($this->userWithPermission('campaigns.create'))
            ->post(route('campaigns.store'), $this->validPayload([
                'channel_id' => 999999,
                'owner_user_id' => 999999,
            ]))->assertSessionHasErrors(['channel_id', 'owner_user_id']);
    }

    public function test_edit_preserves_current_inactive_channel_and_owner(): void
    {
        $channel = Channel::factory()->create(['active' => false]);
        $owner = User::factory()->create(['active' => false]);
        $campaign = Campaign::factory()->create(['channel_id' => $channel->id, 'owner_user_id' => $owner->id]);
        $actor = $this->userWithPermission('campaigns.update');

        $this->actingAs($actor)->get(route('campaigns.edit', $campaign))
            ->assertOk()->assertSee($channel->name)->assertSee($owner->name);

        $this->actingAs($actor)->put(route('campaigns.update', $campaign), $this->validPayload([
            'channel_id' => $channel->id, 'owner_user_id' => $owner->id,
        ]))->assertSessionDoesntHaveErrors();
    }

    public function test_business_validation_rejects_invalid_dates_budget_and_currency(): void
    {
        $this->actingAs($this->userWithPermission('campaigns.create'))
            ->post(route('campaigns.store'), $this->validPayload([
                'start_date' => '2026-08-20', 'end_date' => '2026-08-19',
                'budget' => -1, 'currency' => 'brl',
            ]))->assertSessionHasErrors(['end_date', 'budget', 'currency']);
    }

    public function test_unvalidated_fields_are_not_mass_assigned(): void
    {
        $this->actingAs($this->userWithPermission('campaigns.create'))
            ->post(route('campaigns.store'), $this->validPayload([
                'deleted_at' => now(), 'id' => 999999,
            ]));

        $campaign = Campaign::where('name', 'Campanha Agosto')->firstOrFail();
        $this->assertNull($campaign->deleted_at);
        $this->assertNotSame(999999, $campaign->id);
    }

    public function test_audit_payload_does_not_include_unvalidated_fields(): void
    {
        $this->actingAs($this->userWithPermission('campaigns.create'))
            ->post(route('campaigns.store'), $this->validPayload(['secret' => 'nao registrar']));

        $audit = AuditLog::where('action', 'campaign_created')->firstOrFail();
        $this->assertArrayNotHasKey('secret', $audit->after);
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Campanha Agosto', 'description' => 'Aquisição regional', 'status' => 'planned',
            'channel_id' => null, 'owner_user_id' => null, 'start_date' => '2026-08-20',
            'end_date' => '2026-09-20', 'budget' => '1200.50', 'currency' => 'BRL',
            'utm_source' => 'newsletter', 'utm_medium' => 'email', 'utm_campaign' => 'agosto',
            'utm_content' => 'hero', 'utm_term' => 'saude', 'notes' => 'Nota interna',
        ], $overrides);
    }
}

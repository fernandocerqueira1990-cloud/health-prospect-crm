<?php

namespace Tests\Feature\Campaign;

use App\Models\Campaign;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_valid_campaign_with_expected_casts_and_defaults(): void
    {
        $campaign = Campaign::factory()->create([
            'name' => 'Campanha institucional',
            'status' => 'planned',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'budget' => '1500.50',
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Campanha institucional',
            'status' => 'planned',
            'currency' => 'BRL',
        ]);
        $this->assertSame('2026-09-01', $campaign->start_date->toDateString());
        $this->assertSame('2026-09-30', $campaign->end_date->toDateString());
        $this->assertSame('1500.50', $campaign->budget);
    }

    public function test_campaign_relationships_and_inverse_relationships_resolve(): void
    {
        $channel = Channel::factory()->create();
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'channel_id' => $channel->id,
            'owner_user_id' => $owner->id,
        ]);

        $this->assertTrue($campaign->channel->is($channel));
        $this->assertTrue($campaign->owner->is($owner));
        $this->assertTrue($channel->campaigns()->whereKey($campaign->id)->exists());
        $this->assertTrue($owner->ownedCampaigns()->whereKey($campaign->id)->exists());
    }

    public function test_nullable_relations_are_cleared_when_related_records_are_deleted(): void
    {
        $channel = Channel::factory()->create();
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'channel_id' => $channel->id,
            'owner_user_id' => $owner->id,
        ]);

        $channel->delete();
        $owner->delete();

        $campaign->refresh();
        $this->assertNull($campaign->channel_id);
        $this->assertNull($campaign->owner_user_id);
    }

    public function test_campaign_requires_a_non_blank_name(): void
    {
        $this->expectException(QueryException::class);

        Campaign::factory()->create(['name' => '   ']);
    }

    public function test_database_rejects_unsupported_status(): void
    {
        $this->expectException(QueryException::class);

        Campaign::factory()->create(['status' => 'invalid']);
    }

    public function test_end_date_cannot_be_before_start_date(): void
    {
        $this->expectException(QueryException::class);

        Campaign::factory()->create([
            'start_date' => '2026-09-02',
            'end_date' => '2026-09-01',
        ]);
    }

    public function test_budget_cannot_be_negative(): void
    {
        $this->expectException(QueryException::class);

        Campaign::factory()->create(['budget' => -0.01]);
    }

    public function test_currency_must_use_three_uppercase_letters(): void
    {
        $this->expectException(QueryException::class);

        Campaign::factory()->create(['currency' => 'brl']);
    }

    public function test_foreign_keys_reject_unknown_channel_and_owner(): void
    {
        $this->expectException(QueryException::class);

        Campaign::factory()->create([
            'channel_id' => 999999,
            'owner_user_id' => 999999,
        ]);
    }

    public function test_campaign_uses_soft_deletes(): void
    {
        $campaign = Campaign::factory()->create();

        $campaign->delete();

        $this->assertSoftDeleted('campaigns', ['id' => $campaign->id]);
        $this->assertNull(Campaign::find($campaign->id));
        $this->assertNotNull(Campaign::withTrashed()->find($campaign->id));
    }

    public function test_mass_assignment_does_not_accept_unlisted_attributes(): void
    {
        $campaign = Campaign::create([
            'name' => 'Campanha segura',
            'deleted_at' => now(),
        ]);

        $this->assertNull($campaign->deleted_at);
    }
}

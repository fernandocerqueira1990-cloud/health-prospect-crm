<?php

namespace Tests\Feature\Activity;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_valid_activity(): void
    {
        $activity = Activity::factory()->create([
            'type' => 'call',
            'direction' => 'outbound',
            'duration_minutes' => 30,
        ]);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'type' => 'call',
            'direction' => 'outbound',
            'duration_minutes' => 30,
        ]);

        $this->assertNotNull($activity->occurred_at);
        $this->assertSame(30, $activity->duration_minutes);
    }

    public function test_database_rejects_unsupported_activity_type(): void
    {
        $this->expectException(QueryException::class);

        Activity::factory()->create([
            'type' => 'sms',
        ]);
    }

    public function test_database_rejects_unsupported_direction(): void
    {
        $this->expectException(QueryException::class);

        Activity::factory()->create([
            'direction' => 'sideways',
        ]);
    }

    public function test_database_rejects_zero_duration(): void
    {
        $this->expectException(QueryException::class);

        Activity::factory()->create([
            'duration_minutes' => 0,
        ]);
    }

    public function test_activity_requires_at_least_one_commercial_entity(): void
    {
        $this->expectException(QueryException::class);

        Activity::factory()->create([
            'company_id' => null,
            'contact_id' => null,
            'lead_id' => null,
            'opportunity_id' => null,
        ]);
    }

    public function test_activity_relationships_resolve_related_models(): void
    {
        $company = Company::factory()->create();

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
        ]);

        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
        ]);

        $opportunity = Opportunity::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'lead_id' => $lead->id,
        ]);

        $assignedUser = User::factory()->create();
        $createdByUser = User::factory()->create();

        $activity = Activity::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'assigned_user_id' => $assignedUser->id,
            'created_by_user_id' => $createdByUser->id,
        ]);

        $this->assertTrue($activity->company->is($company));
        $this->assertTrue($activity->contact->is($contact));
        $this->assertTrue($activity->lead->is($lead));
        $this->assertTrue($activity->opportunity->is($opportunity));
        $this->assertTrue($activity->assignedUser->is($assignedUser));
        $this->assertTrue($activity->createdByUser->is($createdByUser));
    }

    public function test_activity_can_be_linked_only_to_opportunity(): void
    {
        $opportunity = Opportunity::factory()->create();

        $activity = Activity::factory()->create([
            'company_id' => null,
            'contact_id' => null,
            'lead_id' => null,
            'opportunity_id' => $opportunity->id,
        ]);

        $this->assertSame(
            $opportunity->id,
            $activity->opportunity_id,
        );
    }

    public function test_commercial_entities_expose_activity_relations(): void
    {
        $company = Company::factory()->create();

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
        ]);

        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
        ]);

        $opportunity = Opportunity::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'lead_id' => $lead->id,
        ]);

        $assignedUser = User::factory()->create();
        $createdByUser = User::factory()->create();

        $activity = Activity::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'assigned_user_id' => $assignedUser->id,
            'created_by_user_id' => $createdByUser->id,
        ]);

        $this->assertTrue(
            $company->activities()->whereKey($activity->id)->exists(),
        );

        $this->assertTrue(
            $contact->activities()->whereKey($activity->id)->exists(),
        );

        $this->assertTrue(
            $lead->activities()->whereKey($activity->id)->exists(),
        );

        $this->assertTrue(
            $opportunity->activities()->whereKey($activity->id)->exists(),
        );

        $this->assertTrue(
            $assignedUser->assignedActivities()
                ->whereKey($activity->id)
                ->exists(),
        );

        $this->assertTrue(
            $createdByUser->createdActivities()
                ->whereKey($activity->id)
                ->exists(),
        );
    }

    public function test_activity_uses_soft_delete(): void
    {
        $activity = Activity::factory()->create();

        $activity->delete();

        $this->assertSoftDeleted('activities', [
            'id' => $activity->id,
        ]);

        $this->assertNotNull(
            Activity::withTrashed()->find($activity->id),
        );
    }
}

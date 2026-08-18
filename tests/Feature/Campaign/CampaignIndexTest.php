<?php

namespace Tests\Feature\Campaign;

use App\Models\Campaign;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class CampaignIndexTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_index_without_filters_lists_campaigns_and_eager_loaded_relations(): void
    {
        $channel = Channel::factory()->create(['name' => 'Canal Operacional']);
        $owner = User::factory()->create(['name' => 'Responsável Operacional']);
        $campaign = Campaign::factory()->create(['name' => 'Campanha Operacional', 'channel_id' => $channel->id, 'owner_user_id' => $owner->id]);

        $this->actingAs($this->admin())->get(route('campaigns.index'))
            ->assertOk()
            ->assertSee($campaign->name)
            ->assertSee($channel->name)
            ->assertSee($owner->name)
            ->assertViewHas('campaigns', fn ($campaigns): bool => $campaigns->first()->relationLoaded('channel') && $campaigns->first()->relationLoaded('owner'));
    }

    public function test_search_finds_name_description_and_utm_campaign_but_not_notes(): void
    {
        $fields = [
            'name' => ['agulha-nome', 'Campanha agulha-nome'],
            'description' => ['agulha-descricao', 'Descrição agulha-descricao'],
            'utm_campaign' => ['agulha-utm', 'agulha-utm'],
        ];

        foreach ($fields as $field => [$term, $value]) {
            $match = Campaign::factory()->create([$field => $value, 'name' => $field === 'name' ? $value : 'Resultado '.$field]);
            $other = Campaign::factory()->create(['name' => 'Sem relação '.$field]);

            $this->actingAs($this->admin())->get(route('campaigns.index', ['q' => strtoupper($term)]))
                ->assertOk()->assertSee($match->name)->assertDontSee($other->name);
        }

        Campaign::factory()->create(['name' => 'Somente nota', 'notes' => 'segredo-notes']);
        $this->actingAs($this->admin())->get(route('campaigns.index', ['q' => 'segredo-notes']))
            ->assertOk()->assertDontSee('Somente nota');
    }

    public function test_search_finds_utm_source(): void
    {
        $match = Campaign::factory()->create(['name' => 'Origem encontrada', 'utm_source' => 'LinkedIn-Especial']);
        Campaign::factory()->create(['name' => 'Outra origem', 'utm_source' => 'newsletter']);

        $this->actingAs($this->admin())->get(route('campaigns.index', ['q' => 'linkedin-especial']))
            ->assertOk()->assertSee($match->name)->assertDontSee('Outra origem');
    }

    public function test_status_channel_and_owner_filters_work_individually(): void
    {
        $channel = Channel::factory()->create();
        $owner = User::factory()->create();
        $match = Campaign::factory()->create(['name' => 'Campanha alvo', 'status' => 'active', 'channel_id' => $channel->id, 'owner_user_id' => $owner->id]);
        $other = Campaign::factory()->create(['name' => 'Campanha fora', 'status' => 'draft']);

        foreach (['status' => 'active', 'channel_id' => $channel->id, 'owner_user_id' => $owner->id] as $filter => $value) {
            $this->actingAs($this->admin())->get(route('campaigns.index', [$filter => $value]))
                ->assertOk()->assertSee($match->name)->assertDontSee($other->name);
        }
    }

    public function test_date_filters_and_combination_of_filters_work(): void
    {
        $channel = Channel::factory()->create();
        $owner = User::factory()->create();
        $match = Campaign::factory()->create([
            'name' => 'Campanha combinada', 'description' => 'regional premium', 'status' => 'planned',
            'channel_id' => $channel->id, 'owner_user_id' => $owner->id,
            'start_date' => '2026-08-10', 'end_date' => '2026-09-10',
        ]);
        $other = Campaign::factory()->create(['name' => 'Campanha fora do período', 'status' => 'draft', 'start_date' => '2026-06-01', 'end_date' => '2026-06-30']);

        $filters = [
            'q' => 'PREMIUM', 'status' => 'planned', 'channel_id' => $channel->id, 'owner_user_id' => $owner->id,
            'start_date_from' => '2026-08-01', 'start_date_to' => '2026-08-31',
            'end_date_from' => '2026-09-01', 'end_date_to' => '2026-09-30',
        ];

        $this->actingAs($this->admin())->get(route('campaigns.index', $filters))
            ->assertOk()->assertSee($match->name)->assertDontSee($other->name);
    }

    public function test_invalid_filters_status_dates_and_sort_are_rejected(): void
    {
        $user = $this->admin();
        $cases = [
            [['channel_id' => 'abc'], 'channel_id'],
            [['owner_user_id' => 999999], 'owner_user_id'],
            [['status' => 'arbitrary'], 'status'],
            [['start_date_from' => 'not-a-date'], 'start_date_from'],
            [['start_date_from' => '2026-08-10', 'start_date_to' => '2026-08-09'], 'start_date_to'],
            [['end_date_from' => '2026-09-10', 'end_date_to' => '2026-09-09'], 'end_date_to'],
            [['sort' => 'name; DROP TABLE campaigns'], 'sort'],
            [['direction' => 'sideways'], 'direction'],
        ];

        foreach ($cases as [$filters, $error]) {
            $this->actingAs($user)->get(route('campaigns.index', $filters))
                ->assertRedirect()->assertSessionHasErrors($error);
        }
    }

    public function test_allowed_sorting_works(): void
    {
        Campaign::factory()->create(['name' => 'Zulu Campanha']);
        Campaign::factory()->create(['name' => 'Alpha Campanha']);

        $response = $this->actingAs($this->admin())->get(route('campaigns.index', ['sort' => 'name', 'direction' => 'asc']))->assertOk();
        $this->assertLessThan(strpos($response->getContent(), 'Zulu Campanha'), strpos($response->getContent(), 'Alpha Campanha'));
    }

    public function test_pagination_preserves_filters_and_sorting(): void
    {
        Campaign::factory()->count(16)->create(['status' => 'active']);

        $this->actingAs($this->admin())->get(route('campaigns.index', ['status' => 'active', 'sort' => 'name', 'direction' => 'asc']))
            ->assertOk()->assertSee('page=2')->assertSee('status=active')->assertSee('sort=name')->assertSee('direction=asc');
    }

    public function test_soft_deleted_campaign_is_not_listed(): void
    {
        Campaign::factory()->create(['name' => 'Campanha visível']);
        $deleted = Campaign::factory()->create(['name' => 'Campanha apagada']);
        $deleted->delete();

        $this->actingAs($this->admin())->get(route('campaigns.index'))
            ->assertOk()->assertSee('Campanha visível')->assertDontSee('Campanha apagada');
    }

    public function test_user_without_view_any_permission_receives_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->get(route('campaigns.index'))->assertForbidden();
    }

    public function test_empty_state_distinguishes_no_campaigns_from_no_filter_results(): void
    {
        $user = $this->admin();
        $this->actingAs($user)->get(route('campaigns.index'))
            ->assertOk()->assertSee('Nenhuma campanha cadastrada ainda.');

        Campaign::factory()->create(['name' => 'Campanha existente']);
        $this->actingAs($user)->get(route('campaigns.index', ['q' => 'sem resultado']))
            ->assertOk()->assertSee('Nenhuma campanha encontrada para os filtros selecionados.');
    }
}

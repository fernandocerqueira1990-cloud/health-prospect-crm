<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_page_uses_the_initial_blade_view(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertViewIs('welcome')
            ->assertSee('Health Prospect CRM');
    }

    public function test_health_check_returns_application_status(): void
    {
        $response = $this->getJson('/health');

        $response
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'service' => 'Health Prospect CRM',
            ]);
    }
}

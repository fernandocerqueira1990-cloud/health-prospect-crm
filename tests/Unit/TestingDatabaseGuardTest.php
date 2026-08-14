<?php

namespace Tests\Unit;

use App\Support\Testing\TestingDatabaseGuard;
use LogicException;
use Tests\TestCase;

class TestingDatabaseGuardTest extends TestCase
{
    public function test_test_environment_uses_the_dedicated_postgresql_database(): void
    {
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('health_prospect_crm_test', config('database.connections.pgsql.database'));
    }

    public function test_guard_rejects_the_development_database_in_testing(): void
    {
        $this->expectException(LogicException::class);

        app(TestingDatabaseGuard::class)->ensureSafe('testing', 'health_prospect_crm');
    }

    public function test_guard_accepts_a_dedicated_test_database(): void
    {
        app(TestingDatabaseGuard::class)->ensureSafe('testing', 'health_prospect_crm_test');

        $this->addToAssertionCount(1);
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;

class FeatureConfigurationTest extends TestCase
{
    public function test_feature_configuration_contains_expected_security_flags(): void
    {
        $this->assertArrayHasKey(
            'public_registration',
            config('features')
        );

        $this->assertArrayHasKey(
            'tester_access',
            config('features')
        );

        $this->assertIsBool(
            config('features.public_registration')
        );

        $this->assertIsBool(
            config('features.tester_access')
        );
    }
}

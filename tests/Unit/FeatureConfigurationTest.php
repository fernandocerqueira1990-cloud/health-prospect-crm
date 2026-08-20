<?php

namespace Tests\Unit;

use Tests\TestCase;

class FeatureConfigurationTest extends TestCase
{
    public function test_public_registration_is_disabled_by_default(): void
    {
        $this->assertFalse(
            (bool) config('features.public_registration')
        );
    }
}

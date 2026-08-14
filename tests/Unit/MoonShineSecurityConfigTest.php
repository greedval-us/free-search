<?php

namespace Tests\Unit;

use App\Support\Observability\MoonShineSecurityConfig;
use PHPUnit\Framework\TestCase;

class MoonShineSecurityConfigTest extends TestCase
{
    public function test_it_normalizes_security_settings(): void
    {
        $config = MoonShineSecurityConfig::fromArray([
            'login_throttle' => [
                'max_attempts' => 0,
                'decay_seconds' => 5,
            ],
            'login_alert' => [
                'channel' => ' daily ',
                'email_enabled' => true,
                'email' => ' security@example.com ',
            ],
        ]);

        $this->assertSame(1, $config->loginMaxAttempts());
        $this->assertSame(15, $config->loginDecaySeconds());
        $this->assertSame('daily', $config->alertChannel());
        $this->assertTrue($config->shouldSendAlertEmail());
        $this->assertSame('security@example.com', $config->alertEmail());
    }
}

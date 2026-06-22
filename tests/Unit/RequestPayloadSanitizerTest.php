<?php

namespace Tests\Unit;

use App\Support\Activity\RequestPayloadSanitizer;
use PHPUnit\Framework\TestCase;

class RequestPayloadSanitizerTest extends TestCase
{
    public function test_it_masks_sensitive_keys_case_insensitively_and_recursively(): void
    {
        $sanitizer = new RequestPayloadSanitizer();

        $result = $sanitizer->sanitize([
            'password' => 'secret-password',
            'activation_token' => 'token-value',
            'Authorization' => 'Bearer top-secret',
            'profile' => [
                'recovery_code' => 'abc-123',
                'cookie' => 'session-cookie',
            ],
            'target' => 'example.com',
        ]);

        $this->assertSame('***', $result['password']);
        $this->assertSame('***', $result['activation_token']);
        $this->assertSame('***', $result['Authorization']);
        $this->assertSame('***', $result['profile']['recovery_code']);
        $this->assertSame('***', $result['profile']['cookie']);
        $this->assertSame('example.com', $result['target']);
    }
}

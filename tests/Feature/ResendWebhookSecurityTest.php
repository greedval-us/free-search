<?php

namespace Tests\Feature;

use Tests\TestCase;

class ResendWebhookSecurityTest extends TestCase
{
    public function test_webhook_is_not_exposed_without_signature_secret(): void
    {
        config()->set('resend.webhook.secret');

        $this->postJson(route('resend.webhook'), [
            'type' => 'email.delivered',
            'data' => [],
        ])->assertNotFound();
    }
}

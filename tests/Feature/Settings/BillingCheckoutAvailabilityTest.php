<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BillingCheckoutAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_flag_alone_does_not_advertise_an_unimplemented_checkout(): void
    {
        config()->set('access.checkout_enabled', true);

        $this->actingAs(User::factory()->create())
            ->get(route('billing.edit'))
            ->assertInertia(fn (Assert $page) => $page->component('settings/Billing')->where('checkoutEnabled', false));
    }

    public function test_checkout_placeholder_does_not_advertise_payment_when_flag_is_enabled(): void
    {
        config()->set('access.checkout_enabled', true);

        $this->actingAs(User::factory()->create())
            ->get(route('settings.placeholder', ['context' => 'checkout', 'plan' => 'plus']))
            ->assertInertia(fn (Assert $page) => $page->component('settings/Placeholder')
                ->where('checkoutEnabled', false)->where('placeholder.context', 'generic')->where('placeholder.plan', ''));
    }
}

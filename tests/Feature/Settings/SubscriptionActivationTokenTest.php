<?php

namespace Tests\Feature\Settings;

use Carbon\CarbonImmutable;
use App\Models\SubscriptionActivationToken;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionActivationTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_activate_subscription_with_token(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $token = SubscriptionActivationToken::query()->create([
            'plan' => User::SUBSCRIPTION_PLAN_PLUS,
        ]);

        $this->assertNotNull($token->expires_at);
        $this->assertTrue(
            $token->expires_at->equalTo(CarbonImmutable::parse($token->created_at)->addMonth()),
        );

        $this->actingAs($user)
            ->post(route('billing.activate-token'), [
                'activation_token' => $token->token,
            ])
            ->assertRedirect(route('billing.edit'));

        $this->assertDatabaseHas('subscription_activation_tokens', [
            'id' => $token->id,
            'used_by_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'plan' => User::SUBSCRIPTION_PLAN_PLUS,
            'status' => UserSubscription::STATUS_ACTIVE,
        ]);

        $subscription = UserSubscription::query()->where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->assertTrue(
            $subscription->ends_at->equalTo(CarbonImmutable::parse($subscription->starts_at)->addMonth()),
        );
    }

    public function test_token_cannot_be_used_twice(): void
    {
        $firstUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $secondUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $token = SubscriptionActivationToken::query()->create([
            'plan' => User::SUBSCRIPTION_PLAN_PRO,
        ]);

        $this->actingAs($firstUser)
            ->post(route('billing.activate-token'), [
                'activation_token' => $token->token,
            ])
            ->assertRedirect(route('billing.edit'));

        $this->actingAs($secondUser)
            ->from(route('billing.edit'))
            ->post(route('billing.activate-token'), [
                'activation_token' => $token->token,
            ])
            ->assertRedirect(route('billing.edit'))
            ->assertSessionHasErrors('activation_token');

        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $secondUser->id,
            'plan' => User::SUBSCRIPTION_PLAN_PRO,
            'status' => UserSubscription::STATUS_ACTIVE,
        ]);
    }

    public function test_expired_token_cannot_be_activated(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $token = SubscriptionActivationToken::query()->create([
            'plan' => User::SUBSCRIPTION_PLAN_PLUS,
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->from(route('billing.edit'))
            ->post(route('billing.activate-token'), [
                'activation_token' => $token->token,
            ])
            ->assertRedirect(route('billing.edit'))
            ->assertSessionHasErrors('activation_token');

        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $user->id,
            'plan' => User::SUBSCRIPTION_PLAN_PLUS,
            'status' => UserSubscription::STATUS_ACTIVE,
        ]);
    }
}

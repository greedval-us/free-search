<?php

namespace App\Services\Subscriptions;

use App\Exceptions\SubscriptionActivationException;
use App\Models\SubscriptionActivationToken;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Access\Enums\AccountPlan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class SubscriptionActivationService
{
    public function __construct(
        private readonly SubscriptionManager $subscriptions,
    ) {}

    public function activate(User $user, string $rawToken): UserSubscription
    {
        $normalizedToken = SubscriptionActivationToken::normalizeToken($rawToken);

        return DB::transaction(function () use ($user, $normalizedToken): UserSubscription {
            /** @var SubscriptionActivationToken|null $token */
            $token = SubscriptionActivationToken::query()
                ->where('token', $normalizedToken)
                ->lockForUpdate()
                ->first();

            if ($token === null) {
                throw SubscriptionActivationException::invalid();
            }

            if ($token->used_at !== null) {
                throw SubscriptionActivationException::used();
            }

            if ($token->expires_at !== null && $token->expires_at->isPast()) {
                throw SubscriptionActivationException::expired();
            }

            $now = CarbonImmutable::now(config('app.timezone'));
            $plan = AccountPlan::tryFrom($token->plan);
            if ($plan === null || $plan === AccountPlan::Free) {
                throw SubscriptionActivationException::invalid();
            }

            $subscription = $this->subscriptions->replace($user, $plan, [
                'source' => 'activation_token',
                'activation_token_id' => $token->id,
            ]);

            $token->forceFill([
                'used_at' => $now,
                'used_by_user_id' => $user->id,
                'used_subscription_id' => $subscription->id,
            ])->save();

            return $subscription;
        });
    }
}

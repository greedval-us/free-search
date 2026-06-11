<?php

namespace App\Services\Subscriptions;

use App\Exceptions\SubscriptionActivationException;
use App\Models\SubscriptionActivationToken;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class SubscriptionActivationService
{
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
                throw new SubscriptionActivationException(SubscriptionActivationException::INVALID);
            }

            if ($token->used_at !== null) {
                throw new SubscriptionActivationException(SubscriptionActivationException::USED);
            }

            if ($token->expires_at !== null && $token->expires_at->isPast()) {
                throw new SubscriptionActivationException(SubscriptionActivationException::EXPIRED);
            }

            $now = CarbonImmutable::now(config('app.timezone'));

            UserSubscription::query()
                ->where('user_id', $user->id)
                ->where('status', UserSubscription::STATUS_ACTIVE)
                ->where('ends_at', '>', $now)
                ->update([
                    'status' => UserSubscription::STATUS_CANCELED,
                    'ends_at' => $now,
                    'updated_at' => $now,
                ]);

            $subscription = UserSubscription::query()->create([
                'user_id' => $user->id,
                'plan' => $token->plan,
                'status' => UserSubscription::STATUS_ACTIVE,
                'starts_at' => $now,
                'ends_at' => $now->addMonth(),
                'metadata' => [
                    'source' => 'activation_token',
                    'activation_token_id' => $token->id,
                ],
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

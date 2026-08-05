<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Support\Notifications\UserNotificationService;
use Illuminate\Console\Command;

final class SendSubscriptionExpiryNotifications extends Command
{
    protected $signature = 'app:notify-subscription-expiry';

    protected $description = 'Send default notifications for subscriptions expiring tomorrow.';

    public function __construct(
        private readonly UserNotificationService $userNotificationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $start = now()->addDay()->startOfDay();
        $end = now()->addDay()->endOfDay();

        $subscriptions = UserSubscription::query()
            ->with('user')
            ->where('status', UserSubscription::STATUS_ACTIVE)
            ->whereBetween('ends_at', [$start, $end])
            ->get();

        $sent = 0;

        foreach ($subscriptions as $subscription) {
            if ($subscription->user === null) {
                continue;
            }

            $this->userNotificationService->sendSubscriptionExpiringTomorrow(
                $subscription->user,
                $subscription,
            );

            $sent++;
        }

        $this->info("Processed {$sent} expiring subscription notifications.");

        return self::SUCCESS;
    }
}

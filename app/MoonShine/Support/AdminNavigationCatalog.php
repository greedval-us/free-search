<?php

declare(strict_types=1);

namespace App\MoonShine\Support;

use App\MoonShine\Resources\AdminAuditLog\AdminAuditLogResource;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\FailedJob\FailedJobResource;
use App\MoonShine\Resources\FeatureUsageDaily\FeatureUsageDailyResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\MoonShine\Resources\QueueJob\QueueJobResource;
use App\MoonShine\Resources\RequestLog\RequestLogResource;
use App\MoonShine\Resources\SubscriptionActivationToken\SubscriptionActivationTokenResource;
use App\MoonShine\Resources\UserSubscription\UserSubscriptionResource;

final class AdminNavigationCatalog
{
    /**
     * @return array<int, array{title: string|\Closure, resources: array<int, class-string>}>
     */
    public static function menuGroups(): array
    {
        return [
            [
                'title' => static fn (): string => __('moonshine::ui.resource.system'),
                'resources' => [
                    MoonShineUserResource::class,
                    MoonShineUserRoleResource::class,
                    AppUserResource::class,
                    RequestLogResource::class,
                ],
            ],
            [
                'title' => static fn (): string => __('admin_panel.navigation.operations'),
                'resources' => [
                    UserSubscriptionResource::class,
                    SubscriptionActivationTokenResource::class,
                    FeatureUsageDailyResource::class,
                    QueueJobResource::class,
                    FailedJobResource::class,
                ],
            ],
            [
                'title' => static fn (): string => __('admin_panel.navigation.security'),
                'resources' => [
                    AdminAuditLogResource::class,
                ],
            ],
        ];
    }

    /**
     * @return array<int, class-string>
     */
    public static function resources(): array
    {
        $all = [];

        foreach (self::menuGroups() as $group) {
            foreach ($group['resources'] as $resourceClass) {
                $all[$resourceClass] = $resourceClass;
            }
        }

        return array_values($all);
    }
}

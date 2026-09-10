<?php

declare(strict_types=1);

namespace App\MoonShine\Support;

use App\MoonShine\Pages\TelegramBotOverviewPage;
use App\MoonShine\Pages\TelegramSessionsPage;
use App\MoonShine\Resources\AdminAuditLog\AdminAuditLogResource;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\FailedJob\FailedJobResource;
use App\MoonShine\Resources\FeatureUsageDaily\FeatureUsageDailyResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\MoonShine\Resources\ParserRun\ParserRunResource;
use App\MoonShine\Resources\QueueJob\QueueJobResource;
use App\MoonShine\Resources\RequestLog\RequestLogResource;
use App\MoonShine\Resources\SubscriptionActivationToken\SubscriptionActivationTokenResource;
use App\MoonShine\Resources\TelegramBotDelivery\TelegramBotDeliveryResource;
use App\MoonShine\Resources\TelegramBotLink\TelegramBotLinkResource;
use App\MoonShine\Resources\UserSubscription\UserSubscriptionResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\ResourceContract;

final class AdminNavigationCatalog
{
    /**
     * @return list<class-string>
     */
    public static function dashboardResources(AdminRole $role): array
    {
        return match ($role) {
            AdminRole::Admin => [
                TelegramBotOverviewPage::class,
                AppUserResource::class,
                UserSubscriptionResource::class,
                ParserRunResource::class,
                FailedJobResource::class,
                MoonShineUserResource::class,
                AdminAuditLogResource::class,
            ],
            AdminRole::Analyst => [
                TelegramBotOverviewPage::class,
                AppUserResource::class,
                FeatureUsageDailyResource::class,
                UserSubscriptionResource::class,
                ParserRunResource::class,
            ],
            AdminRole::Developer => [
                TelegramBotOverviewPage::class,
                ParserRunResource::class,
                RequestLogResource::class,
                QueueJobResource::class,
                FailedJobResource::class,
                FeatureUsageDailyResource::class,
            ],
        };
    }

    /**
     * @param  class-string  $resourceClass
     */
    public static function resourceKey(string $resourceClass): string
    {
        return match ($resourceClass) {
            TelegramBotOverviewPage::class => 'telegram_bot',
            TelegramBotLinkResource::class => 'telegram_bot_links',
            TelegramBotDeliveryResource::class => 'telegram_bot_deliveries',
            AppUserResource::class => 'users',
            UserSubscriptionResource::class => 'subscriptions',
            SubscriptionActivationTokenResource::class => 'activation_tokens',
            FeatureUsageDailyResource::class => 'feature_usage',
            ParserRunResource::class => 'parser_runs',
            RequestLogResource::class => 'request_logs',
            QueueJobResource::class => 'queue',
            FailedJobResource::class => 'failed_jobs',
            MoonShineUserResource::class => 'staff',
            AdminAuditLogResource::class => 'audit',
            MoonShineUserRoleResource::class => 'roles',
            default => 'unknown',
        };
    }

    /**
     * @return array<int, array{title: string|\Closure, icon: string, resources: array<int, class-string>}>
     */
    public static function menuGroups(): array
    {
        return [
            [
                'title' => static fn (): string => __('admin_bot.navigation'),
                'icon' => 'paper-airplane',
                'resources' => [
                    TelegramBotOverviewPage::class,
                    TelegramBotLinkResource::class,
                    TelegramBotDeliveryResource::class,
                ],
            ],
            [
                'title' => static fn (): string => __('admin_panel.navigation.product'),
                'icon' => 'chart-pie',
                'resources' => [
                    AppUserResource::class,
                    FeatureUsageDailyResource::class,
                ],
            ],
            [
                'title' => static fn (): string => __('admin_panel.navigation.revenue'),
                'icon' => 'credit-card',
                'resources' => [
                    UserSubscriptionResource::class,
                    SubscriptionActivationTokenResource::class,
                ],
            ],
            [
                'title' => static fn (): string => __('admin_panel.navigation.operations'),
                'icon' => 'server-stack',
                'resources' => [
                    ParserRunResource::class,
                    TelegramSessionsPage::class,
                    RequestLogResource::class,
                    QueueJobResource::class,
                    FailedJobResource::class,
                ],
            ],
            [
                'title' => static fn (): string => __('admin_panel.navigation.access'),
                'icon' => 'shield-check',
                'resources' => [
                    MoonShineUserResource::class,
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
        return [...self::entriesOfType(ResourceContract::class), MoonShineUserRoleResource::class];
    }

    /** @return list<class-string<PageContract>> */
    public static function pages(): array
    {
        return self::entriesOfType(PageContract::class);
    }

    /** @return list<class-string> */
    private static function entriesOfType(string $contract): array
    {
        $all = [];

        foreach (self::menuGroups() as $group) {
            foreach ($group['resources'] as $resourceClass) {
                if (is_a($resourceClass, $contract, true)) {
                    $all[$resourceClass] = $resourceClass;
                }
            }
        }

        return array_values($all);
    }
}

<?php

declare(strict_types=1);

namespace App\MoonShine\Support;

use App\MoonShine\Pages\TelegramBotOverviewPage;
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
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Support\Enums\Ability;

final class AdminAccess
{
    /**
     * @var array<class-string, list<AdminRole>>
     */
    private const RESOURCE_ROLES = [
        TelegramBotOverviewPage::class => [AdminRole::Admin, AdminRole::Analyst, AdminRole::Developer],
        TelegramBotLinkResource::class => [AdminRole::Admin, AdminRole::Analyst],
        TelegramBotDeliveryResource::class => [AdminRole::Admin, AdminRole::Developer],
        AppUserResource::class => [AdminRole::Admin, AdminRole::Analyst],
        UserSubscriptionResource::class => [AdminRole::Admin, AdminRole::Analyst],
        SubscriptionActivationTokenResource::class => [AdminRole::Admin],
        FeatureUsageDailyResource::class => [AdminRole::Admin, AdminRole::Analyst, AdminRole::Developer],
        ParserRunResource::class => [AdminRole::Admin, AdminRole::Analyst, AdminRole::Developer],
        RequestLogResource::class => [AdminRole::Admin, AdminRole::Developer],
        QueueJobResource::class => [AdminRole::Admin, AdminRole::Developer],
        FailedJobResource::class => [AdminRole::Admin, AdminRole::Developer],
        AdminAuditLogResource::class => [AdminRole::Admin],
        MoonShineUserResource::class => [AdminRole::Admin],
        MoonShineUserRoleResource::class => [AdminRole::Admin],
    ];

    private const READ_ABILITIES = [
        Ability::VIEW_ANY,
        Ability::VIEW,
    ];

    public function role(?MoonshineUser $user): ?AdminRole
    {
        if (! $user instanceof MoonshineUser) {
            return null;
        }

        return AdminRole::fromDatabaseName($user->moonshineUserRole?->name);
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function canViewResource(?MoonshineUser $user, string $resourceClass): bool
    {
        return $this->allows($user, $resourceClass, Ability::VIEW_ANY);
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function allows(?MoonshineUser $user, string $resourceClass, Ability $ability): bool
    {
        if (in_array($resourceClass, [TelegramBotLinkResource::class, TelegramBotDeliveryResource::class], true)
            && ! in_array($ability, self::READ_ABILITIES, true)) {
            return false;
        }

        $role = $this->role($user);

        if ($role === AdminRole::Admin) {
            return true;
        }

        if ($role === null || ! in_array($ability, self::READ_ABILITIES, true)) {
            return false;
        }

        return in_array($role, self::RESOURCE_ROLES[$resourceClass] ?? [], true);
    }
}

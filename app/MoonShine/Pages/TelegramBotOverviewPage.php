<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Resources\TelegramBotDelivery\TelegramBotDeliveryResource;
use App\MoonShine\Resources\TelegramBotLink\TelegramBotLinkResource;
use App\MoonShine\Support\AdminAccess;
use App\MoonShine\Support\TelegramBotAnalyticsService;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Support\Attributes\Icon;
use MoonShine\UI\Components\FlexibleRender;

#[Icon('paper-airplane')]
final class TelegramBotOverviewPage extends Page
{
    public function getTitle(): string
    {
        return __('admin_bot.overview');
    }

    protected function components(): iterable
    {
        $access = app(AdminAccess::class);
        $staff = auth('moonshine')->user();
        $staff = $staff instanceof MoonshineUser ? $staff : null;
        abort_unless($access->canViewResource($staff, self::class), 403);
        $analytics = app(TelegramBotAnalyticsService::class);
        $links = [];
        foreach ([TelegramBotLinkResource::class, TelegramBotDeliveryResource::class] as $resourceClass) {
            if ($access->canViewResource($staff, $resourceClass)) {
                $resource = app($resourceClass);
                $links[] = ['title' => $resource->getTitle(), 'url' => $resource->getUrl()];
            }
        }

        return [
            FlexibleRender::make(view('moonshine.telegram-bot.overview'), [
                'snapshot' => $analytics->snapshot(request()->integer('period')),
                'diagnostics' => $access->canViewResource($staff, TelegramBotDeliveryResource::class)
                    ? $analytics->diagnostics()
                    : null,
                'links' => $links,
            ]),
        ];
    }
}

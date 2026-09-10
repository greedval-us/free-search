<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\TelegramBotLink;

use App\Modules\TelegramBot\Models\BotLink;
use App\MoonShine\Resources\Shared\ReadOnlyModelResource;
use App\MoonShine\Resources\TelegramBotLink\Pages\TelegramBotLinkIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Support\Attributes\Icon;

/** @extends ReadOnlyModelResource<BotLink, TelegramBotLinkIndexPage, null, null> */
#[Icon('link')]
final class TelegramBotLinkResource extends ReadOnlyModelResource
{
    protected string $model = BotLink::class;

    protected string $column = 'telegram_id';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return __('admin_bot.links');
    }

    protected function pages(): array
    {
        return [TelegramBotLinkIndexPage::class];
    }

    protected function search(): array
    {
        return ['id', 'user_id', 'telegram_id', 'user.email'];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $builder->select([
            'id', 'user_id', 'telegraph_chat_id', 'telegram_id', 'locale',
            'notifications_enabled', 'exports_enabled', 'broadcasts_enabled', 'created_at',
        ])->with([
            'user:id,email,telegram_id,email_verified_at,is_blocked',
            'chat:id,chat_id,telegraph_bot_id',
        ]);
        if (! $this->hasQueryParam('sort')) {
            $builder->orderByDesc('id');
        }

        return $builder;
    }
}

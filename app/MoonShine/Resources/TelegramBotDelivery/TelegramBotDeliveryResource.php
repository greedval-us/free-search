<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\TelegramBotDelivery;

use App\Modules\TelegramBot\Models\BotDelivery;
use App\MoonShine\Resources\Shared\ReadOnlyModelResource;
use App\MoonShine\Resources\TelegramBotDelivery\Pages\TelegramBotDeliveryIndexPage;
use App\MoonShine\Support\Formatting\TelegramBotFormatter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Support\Attributes\Icon;

/** @extends ReadOnlyModelResource<BotDelivery, TelegramBotDeliveryIndexPage, null, null> */
#[Icon('paper-airplane')]
final class TelegramBotDeliveryResource extends ReadOnlyModelResource
{
    protected string $model = BotDelivery::class;

    protected string $column = 'kind';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return __('admin_bot.deliveries');
    }

    protected function pages(): array
    {
        return [TelegramBotDeliveryIndexPage::class];
    }

    protected function search(): array
    {
        return ['id', 'link_id', 'kind', 'status'];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $errorCodes = [...TelegramBotFormatter::ERROR_CODES, ''];
        $placeholders = implode(', ', array_fill(0, count($errorCodes), '?'));
        // MoonShine also serializes paginator models in its JSON structure responses.
        $builder->select(['id', 'link_id', 'kind', 'automatic', 'status', 'created_at', 'dispatched_at', 'sent_at'])
            ->selectRaw(
                "CASE WHEN error_code IN ($placeholders) THEN error_code WHEN error_code IS NULL THEN NULL ELSE ? END AS error_code",
                [...$errorCodes, 'other'],
            );
        if (! $this->hasQueryParam('sort')) {
            $builder->orderByDesc('id');
        }

        return $builder;
    }
}

<?php

declare(strict_types=1);

namespace App\MoonShine\Support\Formatting;

use App\Modules\TelegramBot\Models\BotDelivery;

final class TelegramBotFormatter
{
    public const ERROR_CODES = ['file_unavailable', 'file_too_large', 'delivery_failed', 'telegram_400', 'telegram_403'];

    public function statuses(): array
    {
        return [
            BotDelivery::PENDING => __('admin_bot.statuses.pending'),
            BotDelivery::SENT => __('admin_bot.statuses.sent'),
            BotDelivery::SKIPPED => __('admin_bot.statuses.skipped'),
            BotDelivery::FAILED => __('admin_bot.statuses.failed'),
        ];
    }

    public function kinds(): array
    {
        return [
            'notification' => __('admin_bot.kinds.notification'),
            'parser' => __('admin_bot.kinds.parser'),
            'broadcast' => __('admin_bot.kinds.broadcast'),
            'report' => __('admin_bot.kinds.report'),
        ];
    }

    public function status(string $value): string
    {
        return $this->statuses()[$value] ?? __('admin_panel.values.unknown');
    }

    public function kind(string $value): string
    {
        return $this->kinds()[$value] ?? __('admin_panel.values.unknown');
    }

    public function statusColor(string $value): string
    {
        return match ($value) {
            BotDelivery::SENT => 'success',
            BotDelivery::FAILED => 'error',
            BotDelivery::PENDING => 'warning',
            default => 'gray',
        };
    }

    public function error(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return in_array($value, self::ERROR_CODES, true)
            ? $value.' / '.__('admin_bot.errors.'.$value)
            : __('admin_bot.errors.other');
    }
}

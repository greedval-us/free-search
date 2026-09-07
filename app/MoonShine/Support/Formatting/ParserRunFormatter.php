<?php

declare(strict_types=1);

namespace App\MoonShine\Support\Formatting;

use App\Modules\ParserSupport\Enums\ParserRunStatus;
use Illuminate\Support\Str;

final class ParserRunFormatter
{
    public function statusLabel(string $status): string
    {
        $key = "admin_panel.parser_statuses.{$status}";
        $label = __($key);

        return $label === $key ? $status : $label;
    }

    public function statusColor(string $status): string
    {
        $resolved = ParserRunStatus::tryFrom($status);

        if ($resolved === null) {
            $resolved = collect(ParserRunStatus::cases())
                ->first(fn (ParserRunStatus $candidate): bool => $this->statusLabel($candidate->value) === $status);
        }

        return match ($resolved) {
            ParserRunStatus::Completed => 'success',
            ParserRunStatus::Running => 'info',
            ParserRunStatus::Stopped => 'warning',
            ParserRunStatus::Failed => 'error',
            default => 'gray',
        };
    }

    public function moduleLabel(string $module): string
    {
        $key = "admin_panel.quota_modules.{$module}";
        $label = __($key);

        return $label === $key ? Str::headline($module) : $label;
    }

    public function fileSize(?int $bytes): string
    {
        if ($bytes === null || $bytes <= 0) {
            return __('admin_panel.values.not_available');
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $size = $bytes / (1024 ** $power);

        return number_format($size, $power === 0 ? 0 : 1, '.', ' ').' '.$units[$power];
    }

    public function errorSummary(?string $error): string
    {
        return filled($error)
            ? Str::limit(trim((string) $error), 140)
            : __('admin_panel.values.not_available');
    }
}

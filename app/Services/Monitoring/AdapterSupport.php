<?php

namespace App\Services\Monitoring;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\PublicException;
use App\Modules\Telegram\Tracking\TrackingException;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\Cache;

final class AdapterSupport
{
    /** Serialize monitoring operations sharing a configured platform account/key. */
    public static function request(string $platform, Closure $callback): mixed
    {
        $account = match ($platform) {
            'youtube' => config('services.youtube.key'),
            'bluesky' => config('services.bluesky.pds_url').'|'.config('services.bluesky.identifier'),
            'mastodon' => config('services.mastodon.base_url').'|'.config('services.mastodon.token'),
            'news' => config('osint.news_media_intel.searxng.base_url'),
            default => $platform,
        };
        $key = 'monitoring:integration:'.$platform.':'.hash('sha256', (string) $account);
        $lock = Cache::lock($key.':lock', max(30, (int) config('monitoring.api_lease_seconds', 90)));
        if (! $lock->get()) {
            throw new SourceUnavailable('busy', 5);
        }
        try {
            $wait = (int) Cache::get($key.':until', 0) - CarbonImmutable::now()->timestamp;
            if ($wait > 0) {
                throw new SourceUnavailable('cooldown', $wait);
            }

            return $callback();
        } catch (TrackingException $exception) {
            throw new SourceUnavailable($exception->reason, $exception->retryAfter ?: null);
        } catch (ExternalServiceRequestException $exception) {
            $wait = $exception->retryAfter ?? ($exception->status() === 429 ? 60 : null);
            if ($wait !== null) {
                Cache::put($key.':until', CarbonImmutable::now()->timestamp + $wait, $wait);
            }
            throw new SourceUnavailable($exception->errorCode(), $wait);
        } catch (PublicException $exception) {
            throw new SourceUnavailable($exception->errorCode());
        } finally {
            $gap = max(0, (int) config('monitoring.request_gap_seconds', 1));
            if ((int) Cache::get($key.':until', 0) < CarbonImmutable::now()->timestamp + $gap) {
                Cache::put($key.':until', CarbonImmutable::now()->timestamp + $gap, max(1, $gap));
            }
            $lock->release();
        }
    }

    public static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}:\d{2}(?:\.\d{1,9})?(?:Z|[+-]\d{2}:?\d{2})?)?$/D', $value) !== 1) {
            return null;
        }
        $parts = date_parse($value);
        if ($parts['error_count'] || $parts['warning_count']) {
            return null;
        }

        return CarbonImmutable::parse($value, 'UTC')->utc();
    }

    public static function inWindow(?CarbonImmutable $date, CarbonImmutable $from, CarbonImmutable $until): bool
    {
        return $date !== null && $date->gte($from) && $date->lt($until);
    }

    public static function text(mixed $text, int $limit = 12000): string
    {
        return is_scalar($text) ? mb_substr(trim((string) $text), 0, $limit) : '';
    }

    public static function url(mixed $url): ?string
    {
        if (! is_string($url) || mb_strlen($url) > 2048 || preg_match('/[\p{Cc}\s\\\\]/u', $url) !== 0) {
            return null;
        }
        $parts = parse_url($url);

        return is_array($parts) && in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            && ! empty($parts['host']) && ! isset($parts['user']) && ! isset($parts['pass']) ? $url : null;
    }

    public static function pageSize(int $maximum = 100): int
    {
        return min($maximum, max(1, (int) config('monitoring.page_size', 50)));
    }

    public static function nextCursor(mixed $next, ?string $current): ?string
    {
        if ($next === null || $next === '') {
            return null;
        }
        if (! is_string($next) || mb_strlen($next) > 2048 || $next === $current) {
            throw new SourceUnavailable('pagination_stalled');
        }

        return $next;
    }
}

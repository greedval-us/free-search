<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

use App\Models\RequestLog;
use Carbon\Carbon;

final class SeoAuditCrawlBudgetAnalyzer
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(string $host): array
    {
        $from = Carbon::now()->subDays(7);

        $accessLogResult = $this->analyzeFromAccessLog($host, $from);
        if ($accessLogResult !== null) {
            return $accessLogResult + ['source' => 'access_log'];
        }

        return $this->analyzeFromRequestLogs($host, $from) + ['source' => 'request_logs'];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function analyzeFromAccessLog(string $host, Carbon $from): ?array
    {
        $path = storage_path('logs/access.log');
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines) || $lines === []) {
            return null;
        }

        $botHits = 0;
        $statusCounts = ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0];
        $botAgents = [];

        foreach ($lines as $line) {
            if (!is_string($line) || !str_contains($line, $host)) {
                continue;
            }
            if (!$this->isRecentLogLine($line, $from)) {
                continue;
            }

            $ua = $this->extractQuotedPart($line, 5);
            if (!$this->isBotUserAgent($ua)) {
                continue;
            }

            $status = $this->extractStatusCode($line);
            $bucket = $this->statusBucket($status);
            if ($bucket !== null) {
                $statusCounts[$bucket]++;
            }

            $botHits++;
            $botAgents[] = $this->normalizeBotAgent($ua);
        }

        return [
            'periodDays' => 7,
            'host' => $host,
            'botHits' => $botHits,
            'statusBuckets' => $statusCounts,
            'topBotAgents' => $this->topCounts($botAgents, 5),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeFromRequestLogs(string $host, Carbon $from): array
    {
        $rows = RequestLog::query()
            ->where('created_at', '>=', $from)
            ->whereNotNull('user_agent')
            ->get(['status_code', 'user_agent', 'path']);

        $botHits = 0;
        $statusCounts = ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0];
        $botAgents = [];

        foreach ($rows as $row) {
            $ua = (string) ($row->user_agent ?? '');
            if (!$this->isBotUserAgent($ua)) {
                continue;
            }

            $path = (string) ($row->path ?? '');
            if ($host !== '' && $path !== '' && !str_contains($path, '/') && !str_contains($path, $host)) {
                continue;
            }

            $bucket = $this->statusBucket((int) ($row->status_code ?? 0));
            if ($bucket !== null) {
                $statusCounts[$bucket]++;
            }

            $botHits++;
            $botAgents[] = $this->normalizeBotAgent($ua);
        }

        return [
            'periodDays' => 7,
            'host' => $host,
            'botHits' => $botHits,
            'statusBuckets' => $statusCounts,
            'topBotAgents' => $this->topCounts($botAgents, 5),
        ];
    }

    private function isBotUserAgent(string $ua): bool
    {
        $uaLower = mb_strtolower($ua);
        foreach (['googlebot', 'bingbot', 'yandexbot', 'baiduspider', 'duckduckbot', 'crawler', 'spider', 'bot'] as $needle) {
            if (str_contains($uaLower, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeBotAgent(string $ua): string
    {
        $uaLower = mb_strtolower($ua);
        foreach (['googlebot', 'bingbot', 'yandexbot', 'baiduspider', 'duckduckbot'] as $known) {
            if (str_contains($uaLower, $known)) {
                return $known;
            }
        }

        return 'other-bot';
    }

    /**
     * @param array<int, string> $values
     * @return array<int, array<string, mixed>>
     */
    private function topCounts(array $values, int $limit): array
    {
        $counts = [];
        foreach ($values as $value) {
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }
        arsort($counts);

        $rows = [];
        foreach (array_slice($counts, 0, $limit, true) as $agent => $count) {
            $rows[] = ['agent' => $agent, 'count' => $count];
        }

        return $rows;
    }

    private function extractStatusCode(string $line): int
    {
        if (preg_match('/"\\s(\\d{3})\\s/', $line, $match) === 1) {
            return (int) $match[1];
        }

        return 0;
    }

    private function statusBucket(int $status): ?string
    {
        if ($status >= 200 && $status < 300) {
            return '2xx';
        }
        if ($status >= 300 && $status < 400) {
            return '3xx';
        }
        if ($status >= 400 && $status < 500) {
            return '4xx';
        }
        if ($status >= 500 && $status < 600) {
            return '5xx';
        }

        return null;
    }

    private function isRecentLogLine(string $line, Carbon $from): bool
    {
        if (preg_match('/\\[(\\d{2})\\/([A-Za-z]{3})\\/(\\d{4}):(\\d{2}):(\\d{2}):(\\d{2})/', $line, $m) !== 1) {
            return true;
        }
        $monthMap = ['Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12];
        $month = $monthMap[$m[2]] ?? null;
        if ($month === null) {
            return true;
        }
        $date = Carbon::create((int) $m[3], $month, (int) $m[1], (int) $m[4], (int) $m[5], (int) $m[6]);

        return $date->greaterThanOrEqualTo($from);
    }

    private function extractQuotedPart(string $line, int $index): string
    {
        preg_match_all('/"([^"]*)"/', $line, $matches);
        $parts = $matches[1] ?? [];

        return isset($parts[$index]) ? (string) $parts[$index] : '';
    }
}


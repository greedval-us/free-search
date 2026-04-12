<?php

namespace App\Modules\Telegram\Analytics;

use Carbon\Carbon;

class TelegramAnalyticsSnapshotStore
{
    /**
     * @param array<string, mixed> $data
     */
    public function storeSummarySnapshot(
        string $chatUsername,
        Carbon $from,
        Carbon $to,
        string $scorePriority,
        ?string $keyword,
        array $data
    ): void {
        session()->put(
            $this->summarySnapshotKey($chatUsername, $from, $to, $scorePriority, $keyword),
            $data
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSummarySnapshot(
        string $chatUsername,
        Carbon $from,
        Carbon $to,
        string $scorePriority,
        ?string $keyword
    ): ?array {
        $value = session()->get(
            $this->summarySnapshotKey($chatUsername, $from, $to, $scorePriority, $keyword)
        );

        return is_array($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function storeNamedSnapshot(string $role, array $data): void
    {
        session()->put($this->namedSnapshotKey($role), $data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getNamedSnapshot(string $role): ?array
    {
        $value = session()->get($this->namedSnapshotKey($role));

        return is_array($value) ? $value : null;
    }

    /**
     * @param array<string, mixed>|null $snapshot
     */
    public function matchesRequest(
        ?array $snapshot,
        string $chatUsername,
        string $scorePriority,
        ?string $keyword
    ): bool {
        if (!is_array($snapshot)) {
            return false;
        }

        $snapshotChat = strtolower(trim((string) data_get($snapshot, 'range.chatUsername', '')));
        if ($snapshotChat === '' || $snapshotChat !== strtolower(trim($chatUsername))) {
            return false;
        }

        $snapshotPriority = strtolower(trim((string) data_get($snapshot, 'score.priority', '')));
        if ($snapshotPriority === '' || $snapshotPriority !== strtolower(trim($scorePriority))) {
            return false;
        }

        $snapshotKeyword = trim((string) data_get($snapshot, 'range.keyword', ''));
        $requestKeyword = trim((string) ($keyword ?? ''));

        return $snapshotKeyword === $requestKeyword;
    }

    private function summarySnapshotKey(
        string $chatUsername,
        Carbon $from,
        Carbon $to,
        string $scorePriority,
        ?string $keyword
    ): string {
        $normalizedKeyword = trim((string) $keyword);

        return implode(':', [
            'telegram_analytics_summary',
            strtolower(trim($chatUsername)),
            strtolower(trim($scorePriority)),
            $normalizedKeyword,
            $from->copy()->utc()->toIso8601String(),
            $to->copy()->utc()->toIso8601String(),
        ]);
    }

    private function namedSnapshotKey(string $role): string
    {
        return 'telegram_analytics_snapshot_' . strtolower(trim($role));
    }
}


<?php

namespace App\Modules\Telegram\Analytics;

use Carbon\Carbon;
use Illuminate\Contracts\Session\Session;

class TelegramAnalyticsSnapshotStore
{
    public function __construct(
        private readonly Session $session,
    ) {
    }

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
        $this->session->put(
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
        $value = $this->session->get(
            $this->summarySnapshotKey($chatUsername, $from, $to, $scorePriority, $keyword)
        );

        return is_array($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function storeNamedSnapshot(string $role, array $data): void
    {
        $this->session->put($this->namedSnapshotKey($role), $data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getNamedSnapshot(string $role): ?array
    {
        $value = $this->session->get($this->namedSnapshotKey($role));

        return is_array($value) ? $value : null;
    }

    /**
     * @param array<string, mixed>|null $snapshot
     */
    public function matchesRequest(
        ?array $snapshot,
        string $chatUsername,
        string $scorePriority,
        ?string $keyword,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): bool {
        if (!is_array($snapshot)) {
            return false;
        }

        $snapshotChat = $this->normalizeChatUsername((string) data_get($snapshot, 'range.chatUsername', ''));
        if ($snapshotChat === '' || $snapshotChat !== $this->normalizeChatUsername($chatUsername)) {
            return false;
        }

        $snapshotPriority = strtolower(trim((string) data_get($snapshot, 'score.priority', '')));
        if ($snapshotPriority === '' || $snapshotPriority !== strtolower(trim($scorePriority))) {
            return false;
        }

        $snapshotKeyword = $this->normalizeKeyword((string) data_get($snapshot, 'range.keyword', ''));
        $requestKeyword = $this->normalizeKeyword((string) ($keyword ?? ''));

        if ($snapshotKeyword !== $requestKeyword) {
            return false;
        }

        if ($from === null || $to === null) {
            return true;
        }

        $snapshotFromIso = data_get($snapshot, 'range.dateFrom');
        $snapshotToIso = data_get($snapshot, 'range.dateTo');
        if (!is_string($snapshotFromIso) || !is_string($snapshotToIso)) {
            return false;
        }

        try {
            $snapshotFrom = Carbon::parse($snapshotFromIso)->utc()->toIso8601String();
            $snapshotTo = Carbon::parse($snapshotToIso)->utc()->toIso8601String();
        } catch (\Throwable) {
            return false;
        }

        return $snapshotFrom === $from->copy()->utc()->toIso8601String()
            && $snapshotTo === $to->copy()->utc()->toIso8601String();
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
            $this->normalizeChatUsername($chatUsername),
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

    private function normalizeChatUsername(string $chatUsername): string
    {
        return strtolower(trim(ltrim($chatUsername, '@')));
    }

    private function normalizeKeyword(string $keyword): string
    {
        return trim($keyword);
    }
}

<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTrackingSource;
use Closure;

final readonly class TrackingMessageReader
{
    public function __construct(private TrackingConfig $config) {}

    /** @param Closure(string, array): array $request */
    public function checkAccess(int $peer, ?string $keyword, Closure $request): void
    {
        $this->messages($request($keyword === null ? 'getHistory' : 'search', [
            'peer' => $peer,
            'limit' => 1,
            'floodWaitLimit' => 0,
            ...($keyword === null ? [] : $this->searchParameters($keyword)),
        ]));
    }

    /**
     * @param  Closure(string, array): array  $request
     * @return list<array<string, mixed>>
     */
    public function fetch(TelegramTrackingSource $source, Closure $request): array
    {
        $parameters = [
            'peer' => (int) $source->peer_id,
            'offset_id' => $source->offset_id,
            'limit' => $this->config->pageSize(),
            'floodWaitLimit' => 0,
        ];
        if ($source->usesSearch()) {
            $from = ($source->window_start ?? $source->collect_from)->timestamp;
            $parameters += $this->searchParameters($source->tracking->query) + [
                // Telegram's time bounds are exclusive; our fixed window includes both seconds.
                'min_date' => max(0, $from - 1),
                'max_date' => $source->window_end->timestamp + 1,
                // Re-read overlap even below the last matched ID for delayed indexing.
                'min_id' => 0,
            ];
        } else {
            $parameters += [
                'offset_date' => $source->offset_id === 0 ? $source->window_end->timestamp + 1 : 0,
                'min_id' => $source->cursor_id,
            ];
        }
        $result = $request($source->usesSearch() ? 'search' : 'getHistory', $parameters);
        $messages = $this->messages($result);
        if ($source->usesSearch() && $messages === [] && ($result['inexact'] ?? false)) {
            throw new TrackingException('search_incomplete');
        }

        return $messages;
    }

    private function searchParameters(string $keyword): array
    {
        return ['q' => $keyword, 'filter' => ['_' => 'inputMessagesFilterEmpty'], 'hash' => []];
    }

    /** @return list<array<string, mixed>> */
    private function messages(array $result): array
    {
        if (! isset($result['messages']) || ! is_array($result['messages']) || ! array_is_list($result['messages'])) {
            throw new TrackingException('collection_failed');
        }
        foreach ($result['messages'] as $message) {
            if (! is_array($message)) {
                throw new TrackingException('collection_failed');
            }
        }

        return $result['messages'];
    }
}

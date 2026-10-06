<?php

namespace App\Modules\Telegram\Monitoring;

use App\Models\MonitoringSource;
use App\Models\TelegramTracking;
use App\Models\TelegramTrackingSource;
use App\Modules\Telegram\Access\PublicTelegramSource;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Modules\Telegram\Tracking\TrackingException;
use App\Modules\Telegram\Tracking\TrackingPageProcessor;
use App\Services\Monitoring\AdapterSupport;
use App\Services\Monitoring\CollectionPage;
use App\Services\Monitoring\Contracts\SourceAdapter;
use App\Services\Monitoring\SourceUnavailable;
use Carbon\CarbonImmutable;

final readonly class TelegramSourceAdapter implements SourceAdapter
{
    public function __construct(private TrackingGateway $gateway, private TrackingPageProcessor $processor) {}

    public function resolve(string $input): array
    {
        $input = trim($input);
        if (preg_match('~^https?://(?:www\.)?t\.me/([a-z][a-z0-9_]{3,31})/?$~iD', $input, $match) === 1) {
            $input = $match[1];
        }
        $username = PublicTelegramSource::username($input) ?? throw new SourceUnavailable('invalid_source');
        try {
            $resolved = $this->gateway->resolve([$username])[0] ?? throw new SourceUnavailable('group_unavailable');
        } catch (TrackingException $exception) {
            throw new SourceUnavailable($exception->reason, $exception->retryAfter ?: null);
        }

        return ['identity' => (string) $resolved['peer_id'], 'title' => $resolved['title'],
            'configuration' => $resolved + ['mode' => 'all']];
    }

    public function fetch(MonitoringSource $source, CarbonImmutable $from, CarbonImmutable $until, ?string $cursor): CollectionPage
    {
        $configuration = $source->configuration;
        if ((string) ($configuration['peer_id'] ?? '') !== $source->identity
            || empty($configuration['session_name']) || empty($configuration['username'])
            || ($cursor !== null && (! ctype_digit($cursor) || (int) $cursor < 1))) {
            throw new SourceUnavailable('invalid_source');
        }
        $tracking = new TelegramTracking(['mode' => ($configuration['mode'] ?? 'all') === 'user' ? 'user' : 'all',
            'query' => (string) ($configuration['sender_id'] ?? ''), 'status' => TelegramTracking::ACTIVE]);
        $temporary = new TelegramTrackingSource([
            'session_name' => $configuration['session_name'], 'peer_id' => $source->identity,
            'username' => $configuration['username'], 'collection_method' => TelegramTrackingSource::HISTORY,
            'offset_id' => (int) ($cursor ?? 0), 'cursor_id' => 0, 'high_id' => 0,
            'collect_from' => $from, 'window_start' => $from, 'window_end' => $until,
        ]);
        $temporary->setRelation('tracking', $tracking);
        try {
            $raw = $this->gateway->fetch($temporary);
            $page = $this->processor->process($temporary, $raw);
        } catch (TrackingException $exception) {
            throw new SourceUnavailable($exception->reason, $exception->retryAfter ?: null);
        }
        $messages = [];
        foreach ($raw as $message) {
            $messages[(int) ($message['id'] ?? 0)] = $message;
        }
        $items = [];
        foreach ($page->matches as $match) {
            $date = CarbonImmutable::createFromTimestampUTC($match['sent_at']);
            if (! AdapterSupport::inWindow($date, $from, $until)) {
                continue;
            }
            $message = $messages[$match['message_id']] ?? [];
            $items[] = [
                'external_id' => (string) $match['message_id'],
                'url' => 'https://t.me/'.$configuration['username'].'/'.$match['message_id'],
                'title' => '', 'text' => AdapterSupport::text($match['text']), 'author' => $match['sender_id'],
                'published_at' => $date->toIso8601String(),
                'metrics' => ['views' => max(0, (int) ($message['views'] ?? 0)),
                    'forwards' => max(0, (int) ($message['forwards'] ?? 0)),
                    'replies' => max(0, (int) ($message['replies']['replies'] ?? 0))],
            ];
        }

        return new CollectionPage($items, $page->complete ? null : (string) $page->offsetId, $page->complete);
    }
}

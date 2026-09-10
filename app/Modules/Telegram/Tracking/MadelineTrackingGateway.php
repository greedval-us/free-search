<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTrackingSource;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Support\MadelineProto\MadelineProtoConfig;
use App\Support\MadelineProto\MadelineProtoManager;
use Closure;
use danog\MadelineProto\API;
use danog\MadelineProto\RPCError\RateLimitError;
use Illuminate\Support\Facades\Cache;
use Throwable;

final readonly class MadelineTrackingGateway implements TrackingGateway
{
    public function __construct(private MadelineProtoManager $manager, private MadelineProtoConfig $sessions, private TrackingConfig $config) {}

    public function resolve(array $groups): array
    {
        $session = $this->manager->availableSessionNames()[0] ?? throw new TrackingException('session_unavailable');
        $key = 'telegram-tracking:validation:'.hash('sha256', $session.json_encode($groups));

        return Cache::remember($key, $this->config->integer('validation_cache_seconds'), fn () => $this->request($session, function (API $client) use ($groups, $session): array {
            $resolved = [];
            foreach ($groups as $group) {
                if ($resolved !== []) {
                    sleep($this->config->integer('request_gap_seconds'));
                }
                $info = $client->getInfo($group);
                if (! in_array($info['type'] ?? '', ['chat', 'channel', 'supergroup'], true)) {
                    throw new TrackingException('group_unavailable');
                }
                $peer = (string) $info['bot_api_id'];
                // Existence alone is insufficient: verify readable history without joining.
                sleep($this->config->integer('request_gap_seconds'));
                $client->messages->getHistory(peer: (int) $peer, limit: 1, floodWaitLimit: 0);
                $chat = $info['Chat'] ?? [];
                $resolved[$peer] = ['session_name' => $session, 'peer_id' => $peer,
                    'username' => $chat['username'] ?? null, 'title' => mb_substr((string) ($chat['title'] ?? $group), 0, 255)];
            }
            if (count($resolved) !== count($groups)) {
                throw new TrackingException('duplicate_group');
            }

            return array_values($resolved);
        }));
    }

    public function history(TelegramTrackingSource $source): array
    {
        return $this->request($source->session_name, function (API $client) use ($source): array {
            $result = $client->messages->getHistory(peer: (int) $source->peer_id,
                offset_id: $source->offset_id, offset_date: $source->offset_id === 0 ? $source->window_end->timestamp + 1 : 0,
                min_id: $source->cursor_id, limit: $this->config->integer('page_size'), floodWaitLimit: 0);
            if (! isset($result['messages']) || ! is_array($result['messages'])) {
                throw new TrackingException('collection_failed');
            }

            return $result['messages'];
        });
    }

    private function request(string $session, Closure $callback): array
    {
        if (! in_array($session, $this->manager->availableSessionNames(), true)
            || ! file_exists($this->sessions->sessionFilePathFor($session))) {
            throw new TrackingException('session_unavailable');
        }
        $key = 'telegram-tracking:session:'.$session;
        $lock = Cache::lock($key.':lock', $this->config->integer('lease_seconds'));
        if (! $lock->get()) {
            throw new TrackingException('busy', $this->config->integer('request_gap_seconds'));
        }
        try {
            $wait = (int) Cache::get($key.':until', 0) - now()->timestamp;
            if ($wait > 0) {
                throw new TrackingException('cooldown', $wait);
            }
            $client = $this->manager->client($session);
            if ($client->getAuthorization() !== API::LOGGED_IN) {
                throw new TrackingException('session_unavailable');
            }

            return $callback($client);
        } catch (RateLimitError $exception) {
            $wait = max(1, $exception->getWaitTimeLeft());
            Cache::put($key.':until', now()->timestamp + $wait, $wait);
            throw new TrackingException('flood_wait', $wait);
        } catch (TrackingException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $unavailable = preg_match('/CHANNEL_PRIVATE|CHANNEL_INVALID|CHAT_ID_INVALID|PEER_ID_INVALID|USERNAME_NOT_OCCUPIED|USERNAME_INVALID/', $exception->getMessage());
            throw new TrackingException($unavailable ? 'group_unavailable' : 'collection_failed');
        } finally {
            $gap = $this->config->integer('request_gap_seconds');
            if ((int) Cache::get($key.':until', 0) < now()->timestamp + $gap) {
                Cache::put($key.':until', now()->timestamp + $gap, $gap);
            }
            $lock->release();
        }
    }
}

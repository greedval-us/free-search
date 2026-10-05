<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTrackingSource;
use App\Modules\Telegram\Access\PublicTelegramSource;
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
    public function __construct(private MadelineProtoManager $manager, private MadelineProtoConfig $sessions,
        private TrackingConfig $config, private TrackingMessageReader $reader) {}

    public function resolve(array $groups, ?string $keyword = null): array
    {
        $session = $this->manager->availableSessionNames()[0] ?? throw new TrackingException('session_unavailable');

        return $this->request($session, function (API $client) use ($groups, $session, $keyword): array {
            $resolved = [];
            foreach ($groups as $group) {
                if ($resolved !== []) {
                    sleep($this->config->integer('request_gap_seconds'));
                }
                $source = $this->publicSource($client, $group);
                if ($source === null) {
                    throw new TrackingException('group_unavailable');
                }
                $peer = (string) (-1000000000000 - $source['id']);
                // Probe the operation used by this task; never join to obtain access.
                sleep($this->config->integer('request_gap_seconds'));
                $this->reader->checkAccess((int) $peer, $keyword, $this->messagesRequest($client, $source['peer']));
                $chat = $source['chat'];
                $resolved[$peer] = ['session_name' => $session, 'peer_id' => $peer,
                    'username' => $source['username'], 'title' => mb_substr((string) ($chat['title'] ?? $group), 0, 255)];
            }
            if (count($resolved) !== count($groups)) {
                throw new TrackingException('duplicate_group');
            }

            return array_values($resolved);
        });
    }

    public function fetch(TelegramTrackingSource $source): array
    {
        return $this->request($source->session_name, function (API $client) use ($source): array {
            $public = $this->publicSource($client, (string) $source->username);
            if ($public === null || (string) (-1000000000000 - $public['id']) !== (string) $source->peer_id) {
                throw new TrackingException('group_unavailable');
            }

            return $this->reader->fetch($source, $this->messagesRequest($client, $public['peer']));
        });
    }

    private function publicSource(API $client, string $identifier): ?array
    {
        return PublicTelegramSource::resolve($identifier, fn (string $username): array => $client->contacts->resolveUsername(username: $username));
    }

    private function messagesRequest(API $client, array $peer): Closure
    {
        return static function (string $method, array $parameters) use ($client, $peer): array {
            $parameters['peer'] = $peer;

            return $client->messages->{$method}(...$parameters);
        };
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
            $unavailable = preg_match('/CHANNEL_PRIVATE|CHANNEL_INVALID|CHAT_ID_INVALID|CHAT_ADMIN_REQUIRED|PEER_ID_INVALID|PEER_ID_NOT_SUPPORTED|USERNAME_NOT_OCCUPIED|USERNAME_INVALID/', $exception->getMessage());
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

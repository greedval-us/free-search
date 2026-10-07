<?php

namespace App\Modules\Telegram\Access;

use Closure;
use danog\DialogId\DialogId;
use danog\MadelineProto\API;

final class PublicTelegramSource
{
    public static function resolveWithClient(API $client, string $identifier): ?array
    {
        return self::resolve($identifier, fn (string $username): array => self::refreshedInfo($client, $username));
    }

    public static function refreshedInfo(API $client, string $username): array
    {
        $client->refreshPeerCache($username);

        return $client->getInfo($username);
    }

    public static function username(mixed $identifier): ?string
    {
        if (! is_string($identifier) || preg_match('/^@?[a-z][a-z0-9_]{3,31}$/iD', trim($identifier)) !== 1) {
            return null;
        }

        $username = strtolower(ltrim(trim($identifier), '@'));

        return $username === 'self' ? null : $username;
    }

    /**
     * Resolve the username on Telegram for every operation; session peer caches are not authorization.
     *
     * @param  Closure(string): array  $resolveUsername
     * @return array{username: string, id: int, peer: array, channel: array, chat: array}|null
     */
    public static function resolve(string $identifier, Closure $resolveUsername): ?array
    {
        $username = self::username($identifier);
        if ($username === null) {
            return null;
        }

        $resolved = $resolveUsername($username);
        if (array_key_exists('Chat', $resolved)) {
            $chat = $resolved['Chat'];
            $id = is_array($chat) ? self::channelId($chat['id'] ?? null) : null;
            if (! in_array($resolved['type'] ?? '', ['channel', 'supergroup'], true)
                || ! is_array($chat) || ($chat['_'] ?? '') !== 'channel' || $id === null
                || self::channelId($resolved['channel_id'] ?? null) !== $id
                || (isset($resolved['bot_api_id']) && self::channelId($resolved['bot_api_id']) !== $id)) {
                return null;
            }

            $chat['id'] = $id;
            $resolved = ['peer' => ['_' => 'peerChannel', 'channel_id' => $id], 'chats' => [$chat]];
        }
        $peer = $resolved['peer'] ?? [];
        if (($peer['_'] ?? '') !== 'peerChannel' || (int) ($peer['channel_id'] ?? 0) <= 0) {
            return null;
        }

        foreach ($resolved['chats'] ?? [] as $chat) {
            if (! is_array($chat) || ($chat['_'] ?? '') !== 'channel'
                || (int) ($chat['id'] ?? 0) !== (int) $peer['channel_id']
                || empty($chat['access_hash']) || ($chat['min'] ?? false)
                || ($chat['restricted'] ?? false) || ($chat['noforwards'] ?? false)
                || (! ($chat['broadcast'] ?? false) && ! ($chat['megagroup'] ?? false))
                || ! self::hasUsername($chat, $username)) {
                continue;
            }

            $id = (int) $chat['id'];
            $accessHash = $chat['access_hash'];

            return [
                'username' => $username,
                'id' => $id,
                'peer' => ['_' => 'inputPeerChannel', 'channel_id' => $id, 'access_hash' => $accessHash],
                'channel' => ['_' => 'inputChannel', 'channel_id' => $id, 'access_hash' => $accessHash],
                'chat' => $chat,
            ];
        }

        return null;
    }

    /**
     * Metadata lookup alone never authorizes reading a linked discussion.
     *
     * @param  array{peer: array}  $source
     * @param  Closure(array): array  $fullInfo
     * @param  Closure(int): array  $linkedInfo
     * @param  Closure(string): array  $resolveUsername
     * @return array{username: string, id: int, peer: array, channel: array, chat: array}|null
     */
    public static function resolveDiscussion(array $source, Closure $fullInfo, Closure $linkedInfo, Closure $resolveUsername): ?array
    {
        $info = $fullInfo($source['peer']);
        $linkedId = self::channelId($info['full']['linked_chat_id'] ?? null);
        if ($linkedId === null) {
            return null;
        }

        $linked = $linkedInfo(-1000000000000 - $linkedId);
        $chat = $linked['Chat'] ?? $linked['chat'] ?? [];
        $username = self::username($chat['username'] ?? null);
        if ($username === null) {
            foreach ($chat['usernames'] ?? [] as $alias) {
                if (is_array($alias) && ($alias['active'] ?? false)) {
                    $username = self::username($alias['username'] ?? null);
                    if ($username !== null) {
                        break;
                    }
                }
            }
        }
        if ($username === null) {
            return null;
        }

        $discussion = self::resolve($username, $resolveUsername);

        return $discussion !== null && $discussion['id'] === $linkedId && ($discussion['chat']['megagroup'] ?? false)
            ? $discussion
            : null;
    }

    public static function matchesPeer(mixed $peer, int $channelId): bool
    {
        if (is_array($peer)) {
            return ($peer['_'] ?? '') === 'peerChannel' && (int) ($peer['channel_id'] ?? 0) === $channelId;
        }

        return (is_int($peer) || (is_string($peer) && preg_match('/^-\d+$/D', $peer) === 1))
            && (int) $peer === DialogId::fromSupergroupOrChannelId($channelId);
    }

    private static function channelId(mixed $id): ?int
    {
        if (! is_int($id) && (! is_string($id) || preg_match('/^-?\d+$/D', $id) !== 1)) {
            return null;
        }

        $id = (int) $id;
        if ($id > 0) {
            return $id;
        }

        try {
            return $id < 0 && DialogId::isSupergroupOrChannel($id) ? DialogId::toSupergroupOrChannelId($id) : null;
        } catch (\AssertionError) {
            return null;
        }
    }

    private static function hasUsername(array $chat, string $username): bool
    {
        if (self::username($chat['username'] ?? null) === $username) {
            return true;
        }

        foreach ($chat['usernames'] ?? [] as $alias) {
            if (is_array($alias) && ($alias['active'] ?? false)
                && self::username($alias['username'] ?? null) === $username) {
                return true;
            }
        }

        return false;
    }
}

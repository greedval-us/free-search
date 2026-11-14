<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\PublicException;
use App\Modules\Telegram\Access\PublicTelegramSource;
use App\Modules\Telegram\Actions\AbstractTelegramAction;
use App\Modules\Telegram\DTO\Request\SearchMessagesDTO;
use Closure;
use danog\MadelineProto\PeerNotInDbException;
use danog\MadelineProto\RPCError\UsernameInvalidError;
use Illuminate\Support\Arr;

class MessagesAction extends AbstractTelegramAction
{
    public function execute(array $filter): ?array
    {
        $client = $this->madeline();

        try {
            $source = $this->publicSource($client, (string) ($filter['peer'] ?? ''));
            if ($source === null || filled($filter['from_id'] ?? null) || filled($filter['saved_peer_id'] ?? null)) {
                return null;
            }

            return $this->loadMessages(
                $filter,
                $source['peer'],
                fn (string $method, array $parameters): array => $this->executeWithRetry(
                    callback: fn () => $client->messages->{$method}(...$parameters),
                    context: $filter,
                ),
                fn (string $username): array => PublicTelegramSource::refreshedInfo($client, $username),
            );
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logError($e, $filter);

            return null;
        }
    }

    protected function loadMessages(array $filter, array $peer, Closure $request, Closure $resolveAuthor): array
    {
        $filter['peer'] = $peer;
        if (SearchMessagesDTO::usesHistory($filter)) {
            $parameters = Arr::only($filter, ['peer', 'offset_id', 'add_offset', 'limit', 'max_id', 'min_id', 'hash']);
            $parameters['offset_date'] = $filter['offset_id'] === 0 ? $filter['max_date'] : 0;

            return $request('getHistory', $parameters);
        }

        if (isset($filter['authorUsername'])) {
            $filter['from_id'] = $this->publicAuthorPeer($filter['authorUsername'], $resolveAuthor);
        }
        unset($filter['authorUsername']);

        return $request('search', $filter);
    }

    private function publicAuthorPeer(string $identifier, Closure $resolveAuthor): array
    {
        $username = PublicTelegramSource::username($identifier);
        $unavailable = new ExternalServiceRequestException('errors.api.telegram.author_peer_resolve_failed', 422, 'telegram_author_unavailable');
        if ($username === null) {
            throw $unavailable;
        }

        try {
            $info = $resolveAuthor($username);
        } catch (PeerNotInDbException|UsernameInvalidError) {
            throw $unavailable;
        }

        $user = $info['User'] ?? [];
        if (($user['_'] ?? null) === 'user' && ! ($user['deleted'] ?? false) && ! ($user['min'] ?? false)
            && (int) ($user['id'] ?? 0) > 0 && ! empty($user['access_hash'])
            && PublicTelegramSource::hasUsername($user, $username)) {
            return ['_' => 'inputPeerUser', 'user_id' => (int) $user['id'], 'access_hash' => $user['access_hash']];
        }

        // Messages can also be sent on behalf of a public channel.
        $channel = PublicTelegramSource::resolve($username, static fn (): array => $info);

        return $channel['peer'] ?? throw $unavailable;
    }
}

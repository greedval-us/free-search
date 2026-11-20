<?php

namespace App\Modules\Telegram;

use App\Exceptions\PublicException;
use App\Facades\MadelineProto;
use App\Modules\Telegram\Access\PublicTelegramSource;
use App\Modules\Telegram\Actions\Request\CommentsAction;
use App\Modules\Telegram\Actions\Request\InfoAction;
use App\Modules\Telegram\Actions\Request\MessagesAction;
use App\Modules\Telegram\Actions\Request\ParticipantsAction;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Request\SearchMessagesDTO;
use App\Modules\Telegram\DTO\Response\Info\ChannelInfoDTO;
use App\Modules\Telegram\DTO\Response\Messages\ChannelMessagesDTO;
use App\Modules\Telegram\DTO\Response\Participants\ChannelParticipantsDTO;
use App\Support\MadelineProto\MadelineProtoManager;
use danog\MadelineProto\API;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class TelegramService implements TelegramGatewayInterface
{
    private const MIN_COMMENTS_LIMIT = 1;

    private const COMMENTS_MAX_PAGES = 1;

    public function __construct(
        private readonly InfoAction $infoAction,
        private readonly MessagesAction $messagesAction,
        private readonly ParticipantsAction $participantsAction,
        private readonly CommentsAction $commentsAction,
    ) {}

    public function getInfo(string $id): ?ChannelInfoDTO
    {
        if (PublicTelegramSource::username($id) === null) {
            return null;
        }

        try {
            $data = $this->infoAction->execute(id: $id);
            if (! $this->isValidInfoResponse($data)) {
                return null;
            }

            return new ChannelInfoDTO($this->publicInfo($data));
        } catch (\Throwable $e) {
            Log::warning('[TelegramService::getInfo] Failed to load channel info', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getMessages(array $filter): ?ChannelMessagesDTO
    {
        if (PublicTelegramSource::username($filter['peer'] ?? $filter['chatUsername'] ?? $filter['chat'] ?? null) === null
            || filled($filter['from_id'] ?? null) || filled($filter['saved_peer_id'] ?? null)
            || isset($filter['authorId'])) {
            return null;
        }

        try {
            $parameters = SearchMessagesDTO::fromArray(params: $filter)->toArray();
            $data = $this->messagesAction->execute(filter: $parameters);

            if (! $this->isValidMessagesResponse($data)) {
                return null;
            }

            if (SearchMessagesDTO::usesHistory($parameters) && ($parameters['min_date'] > 0 || $parameters['max_date'] > 0)) {
                // getHistory supports an upper date cursor, but has no lower date filter.
                $data['messages'] = array_values(array_filter($data['messages'], static function (array $message) use ($parameters): bool {
                    $date = (int) ($message['date'] ?? 0);

                    return $date > $parameters['min_date'] && ($parameters['max_date'] === 0 || $date < $parameters['max_date']);
                }));
            }

            return new ChannelMessagesDTO($data);
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('[TelegramService::getMessages] Failed to load messages', [
                'filter' => $this->sanitizeFilterForLogs($filter),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getParticipants(array $filter): ?ChannelParticipantsDTO
    {
        // A public channel does not make its membership public to an unprivileged user.
        return null;
    }

    public function getComments(
        string $channel,
        int $postId,
        int $limit = self::DEFAULT_COMMENTS_LIMIT,
        int $offsetId = self::INITIAL_COMMENTS_OFFSET_ID,
    ): array {
        try {
            if (PublicTelegramSource::username($channel) === null) {
                return $this->failedCommentsPage();
            }

            $safeLimit = max(self::MIN_COMMENTS_LIMIT, min($limit, 100));
            $result = $this->commentsAction->execute(
                channelId: $channel,
                postIds: [$postId],
                delayMs: 0,
                commentsPerRequest: $safeLimit,
                maxPages: self::COMMENTS_MAX_PAGES,
                offsetId: max(self::INITIAL_COMMENTS_OFFSET_ID, $offsetId),
            );

            if (! isset($result[0]) || ! is_array($result[0]) || isset($result[0]['error'])
                || ! is_array($result[0]['comments'] ?? null)) {
                return $this->failedCommentsPage();
            }

            return [
                'ok' => true,
                'items' => is_array($result[0]['comments'] ?? null) ? $result[0]['comments'] : [],
                'nextOffsetId' => isset($result[0]['next_offset_id']) ? (int) $result[0]['next_offset_id'] : null,
                'hasMore' => (bool) ($result[0]['has_more'] ?? false),
                'total' => (int) ($result[0]['total'] ?? 0),
            ];
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('[TelegramService::getComments] Failed to load comments', [
                'channel' => $channel,
                'post_id' => $postId,
                'limit' => $limit,
                'offset_id' => $offsetId,
                'error' => $e->getMessage(),
            ]);

            return $this->failedCommentsPage();
        }
    }

    public function getMessageMedia(string $channel, int $messageId): ?array
    {
        if (PublicTelegramSource::username($channel) === null || $messageId <= 0) {
            return null;
        }

        try {
            $client = $this->madeline();
            $source = PublicTelegramSource::resolveWithClient($client, $channel);
            if ($source === null) {
                return null;
            }
            $response = $client->channels->getMessages(channel: $source['channel'], id: [$messageId]);

            $messages = is_array($response['messages'] ?? null) ? $response['messages'] : [];
            $message = $messages[0] ?? null;

            if (! is_array($message) || (int) ($message['id'] ?? 0) !== $messageId
                || ! PublicTelegramSource::matchesPeer($message['peer_id'] ?? null, $source['id'])
                || ($message['noforwards'] ?? false)) {
                return null;
            }

            $media = $message['media'] ?? null;
            if (! is_array($media)) {
                return null;
            }

            $downloadInfo = $client->getDownloadInfo($media);
            if (! is_array($downloadInfo) || empty($downloadInfo)) {
                return null;
            }

            return [
                'downloadToFile' => static fn (string $path): string => $client->downloadToFile($media, $path),
                'download' => Arr::only($downloadInfo, ['name', 'ext']),
            ];
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('[TelegramService::getMessageMedia] Failed to load media', [
                'channel' => $channel,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function madeline(): API
    {
        /** @var MadelineProtoManager $manager */
        $manager = MadelineProto::getFacadeRoot();

        return $manager->authorizedClient();
    }

    private function isValidInfoResponse(?array $data): bool
    {
        return is_array($data) && in_array($data['type'] ?? '', ['channel', 'supergroup'], true)
            && ($data['Chat']['_'] ?? $data['chat']['_'] ?? '') === 'channel';
    }

    private function failedCommentsPage(): array
    {
        return ['ok' => false, 'items' => [], 'nextOffsetId' => null, 'hasMore' => false, 'total' => 0];
    }

    private function isValidMessagesResponse(?array $data): bool
    {
        if (! is_array($data) || ! in_array($data['_'] ?? '', ['messages.messages', 'messages.messagesSlice', 'messages.channelMessages'], true)
            || ! is_array($data['messages'] ?? null) || ! array_is_list($data['messages'])) {
            return false;
        }
        foreach ($data['messages'] as $message) {
            if (! is_array($message) || (int) ($message['id'] ?? 0) <= 0) {
                return false;
            }
        }

        return true;
    }

    private function publicInfo(array $data): array
    {
        $result = Arr::only($data, ['channel_id', 'bot_api_id', 'type', 'id', 'inserted']);
        $result['Chat'] = Arr::only($data['Chat'] ?? $data['chat'] ?? [], [
            '_', 'id', 'title', 'username', 'usernames', 'photo', 'date', 'broadcast', 'megagroup',
            'verified', 'restricted', 'min', 'scam', 'fake', 'has_link', 'has_geo', 'forum',
            'gigagroup', 'signatures', 'participants_count',
        ]);
        $result['full'] = Arr::only($data['full'] ?? [], [
            '_', 'id', 'about', 'participants_count', 'chat_photo', 'pinned_msg_id',
            'available_reactions', 'reactions_limit', 'slowmode_seconds', 'ttl_period', 'stargifts_count',
        ]);

        return $this->removeInternalIdentifiers($result);
    }

    private function removeInternalIdentifiers(array $payload): array
    {
        unset($payload['access_hash'], $payload['file_reference']);
        foreach ($payload as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $payload[$key] = $this->removeInternalIdentifiers((array) $value);
            }
        }

        return $payload;
    }

    private function sanitizeFilterForLogs(array $filter): array
    {
        $sensitiveKeys = ['api_hash', 'api_id', 'token', 'password', 'session', 'phone'];
        foreach ($sensitiveKeys as $key) {
            if (array_key_exists($key, $filter)) {
                $filter[$key] = '[redacted]';
            }
        }

        return $filter;
    }
}

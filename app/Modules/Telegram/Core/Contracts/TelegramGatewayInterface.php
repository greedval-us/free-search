<?php

namespace App\Modules\Telegram\Core\Contracts;

use App\Modules\Telegram\DTO\Response\Info\ChannelInfoDTO;
use App\Modules\Telegram\DTO\Response\Messages\ChannelMessagesDTO;
use App\Modules\Telegram\DTO\Response\Participants\ChannelParticipantsDTO;

interface TelegramGatewayInterface
{
    public const DEFAULT_COMMENTS_LIMIT = 20;

    public const INITIAL_COMMENTS_OFFSET_ID = 0;

    public function getInfo(string $id): ?ChannelInfoDTO;

    /**
     * @param  array<string, mixed>  $filter
     */
    public function getMessages(array $filter): ?ChannelMessagesDTO;

    /**
     * @param  array<string, mixed>  $filter
     */
    public function getParticipants(array $filter): ?ChannelParticipantsDTO;

    /**
     * @return array<string, mixed>
     */
    public function getComments(
        string $channel,
        int $postId,
        int $limit = self::DEFAULT_COMMENTS_LIMIT,
        int $offsetId = self::INITIAL_COMMENTS_OFFSET_ID,
    ): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getMessageMedia(string $channel, int $messageId): ?array;

    /**
     * @param  array<string, mixed>  $media
     */
    public function downloadMediaToFile(array $media, string $path): string;
}

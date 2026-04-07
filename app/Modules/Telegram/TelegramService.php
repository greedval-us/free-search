<?php

namespace App\Modules\Telegram;

use App\Modules\Telegram\Actions\Request\InfoAction;
use App\Modules\Telegram\Actions\Request\MessagesAction;
use App\Modules\Telegram\Actions\Request\ParticipantsAction;

use App\Modules\Telegram\DTO\Request\SearchMessagesDTO;
use App\Modules\Telegram\DTO\Request\SearchParticipantsDTO;
use App\Modules\Telegram\DTO\Response\Messages\ChannelMessagesDTO;
use App\Modules\Telegram\DTO\Response\Participants\ChannelParticipantsDTO;
use App\Modules\Telegram\DTO\Response\Info\ChannelInfoDTO;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function __construct(
        private readonly InfoAction $infoAction,
        private readonly MessagesAction $messagesAction,
        private readonly ParticipantsAction $participantsAction,
    ) {}

    public function getInfo(string $id): ?ChannelInfoDTO
    {
        try {
            $data = $this->infoAction->execute(id: $id);
            if (!$this->isValidInfoResponse($data)) {
                return null;
            }

            return new ChannelInfoDTO($data);
        } catch (\Throwable $e) {
            Log::warning('[TelegramService::getInfo] Failed to load channel info', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getMessages(array $filter): ChannelMessagesDTO|null
    {
        try {
            $dto = SearchMessagesDTO::fromArray(params: $filter);
            $data = $this->messagesAction->execute(filter: $dto->toArray());

            if (!$this->isValidMessagesResponse($data)) {
                return null;
            }

            return new ChannelMessagesDTO($data);
        } catch (\Throwable $e) {
            Log::warning('[TelegramService::getMessages] Failed to load messages', [
                'filter' => $this->sanitizeFilterForLogs($filter),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getParticipants(array $filter): ChannelParticipantsDTO|null
    {
        try {
            $dto = SearchParticipantsDTO::fromArray(params: $filter);
            $data = $this->participantsAction->execute(filter: $dto->toArray());

            if (!$this->isValidParticipantsResponse($data)) {
                return null;
            }

            return new ChannelParticipantsDTO($data);
        } catch (\Throwable $e) {
            Log::warning('[TelegramService::getParticipants] Failed to load participants', [
                'filter' => $this->sanitizeFilterForLogs($filter),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function isValidInfoResponse(?array $data): bool
    {
        return is_array($data) && !empty($data);
    }

    private function isValidMessagesResponse(?array $data): bool
    {
        return is_array($data) && isset($data['_']) && isset($data['messages']);
    }

    private function isValidParticipantsResponse(?array $data): bool
    {
        return is_array($data) && isset($data['_']) && isset($data['participants']);
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

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

class TelegramService
{
    public function __construct(
        private readonly InfoAction $infoAction,
        private readonly MessagesAction $messagesAction,
        private readonly ParticipantsAction $participantsAction,
    ) {}

    public function getInfo(string $id): ?ChannelInfoDTO
    {
        $data = $this->infoAction->execute(id: $id);

        if (!$data) {
            return null;
        }

        return new ChannelInfoDTO($data);
    }

    public function getMessages(array $filter): ChannelMessagesDTO|null
    {
        $dto = SearchMessagesDTO::fromArray(params: $filter);
        $data = $this->messagesAction->execute(filter: $dto->toArray());

        if (!$data) {
            return null;
        }

        return new ChannelMessagesDTO($data);
    }

    public function getParticipants(array $filter): ChannelParticipantsDTO|null
    {
        $dto = SearchParticipantsDTO::fromArray(params: $filter);
        $data = $this->participantsAction->execute(filter: $dto->toArray());

        if (!$data) {
            return null;
        }

        return new ChannelParticipantsDTO($data);
    }
}

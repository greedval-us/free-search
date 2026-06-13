<?php

namespace Tests\Unit;

use App\Exceptions\Domain\DomainValidationException;
use App\Modules\Telegram\DTO\Request\SearchMessagesDTO;
use App\Modules\Telegram\DTO\Request\SearchParticipantsDTO;
use Tests\TestCase;

class TelegramRequestDtoValidationTest extends TestCase
{
    public function test_search_messages_dto_requires_peer(): void
    {
        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Messages search requires non-empty "peer" or "chatUsername".');

        SearchMessagesDTO::fromArray([]);
    }

    public function test_search_messages_dto_rejects_inverted_date_range(): void
    {
        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('"min_date" must be less than or equal to "max_date".');

        SearchMessagesDTO::fromArray([
            'peer' => '@channel',
            'min_date' => 200,
            'max_date' => 100,
        ]);
    }

    public function test_search_participants_dto_requires_chat_username(): void
    {
        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Participants search requires non-empty "chatUsername" or "chat".');

        SearchParticipantsDTO::fromArray([]);
    }

    public function test_search_participants_dto_rejects_unsupported_filter(): void
    {
        $this->expectException(DomainValidationException::class);
        $this->expectExceptionMessage('Unsupported participants filter: unsupported');

        SearchParticipantsDTO::fromArray([
            'chatUsername' => 'channel',
            'filter' => 'unsupported',
        ]);
    }
}

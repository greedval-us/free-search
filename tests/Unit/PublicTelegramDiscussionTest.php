<?php

namespace Tests\Unit;

use App\Modules\Telegram\Access\PublicTelegramSource;
use PHPUnit\Framework\TestCase;

class PublicTelegramDiscussionTest extends TestCase
{
    public function test_private_linked_discussion_is_rejected_before_content_can_be_read(): void
    {
        $result = PublicTelegramSource::resolveDiscussion(
            ['peer' => ['_' => 'inputPeerChannel', 'channel_id' => 55]],
            static fn (): array => ['full' => ['linked_chat_id' => 77]],
            static fn (): array => ['Chat' => ['id' => 77, 'megagroup' => true]],
            fn (): array => $this->fail('A private discussion must not be resolved or read.'),
        );

        $this->assertNull($result);
    }

    public function test_public_linked_discussion_is_pinned_to_the_linked_channel_id(): void
    {
        $lookupIds = [];
        $usernames = [];

        $result = PublicTelegramSource::resolveDiscussion(
            ['peer' => ['_' => 'inputPeerChannel', 'channel_id' => 55]],
            static fn (): array => ['full' => ['linked_chat_id' => 77]],
            static function (int $id) use (&$lookupIds): array {
                $lookupIds[] = $id;

                return ['Chat' => ['id' => 77, 'username' => 'discussion']];
            },
            function (string $username) use (&$usernames): array {
                $usernames[] = $username;

                return $this->discussion();
            },
        );

        $this->assertSame([-1000000000077], $lookupIds);
        $this->assertSame(['discussion'], $usernames);
        $this->assertSame(77, $result['id']);
    }

    public function test_renamed_or_reassigned_discussion_username_is_not_authorized(): void
    {
        $result = PublicTelegramSource::resolveDiscussion(
            ['peer' => []],
            static fn (): array => ['full' => ['linked_chat_id' => 88]],
            static fn (): array => ['Chat' => ['username' => 'discussion']],
            fn (): array => $this->discussion(),
        );

        $this->assertNull($result);
    }

    public function test_a_missing_link_cannot_be_treated_as_public_discussion(): void
    {
        $result = PublicTelegramSource::resolveDiscussion(
            ['peer' => []],
            static fn (): array => ['full' => []],
            fn (): array => $this->fail('A missing link must not reach metadata lookup.'),
            fn (): array => $this->fail('A missing link must not reach public resolution.'),
        );

        $this->assertNull($result);
    }

    public function test_active_public_discussion_alias_is_supported(): void
    {
        $result = PublicTelegramSource::resolveDiscussion(
            ['peer' => []],
            static fn (): array => ['full' => ['linked_chat_id' => 77]],
            static fn (): array => ['Chat' => ['usernames' => [['username' => 'discussion', 'active' => true]]]],
            fn (): array => $this->discussion(),
        );

        $this->assertSame(77, $result['id']);
    }

    private function discussion(): array
    {
        return ['peer' => ['_' => 'peerChannel', 'channel_id' => 77], 'chats' => [
            ['_' => 'channel', 'id' => 77, 'access_hash' => 123, 'username' => 'discussion', 'megagroup' => true],
        ]];
    }
}

<?php

namespace Tests\Feature;

use App\Modules\Telegram\Actions\Request\CommentsAction;
use App\Modules\Telegram\Actions\Request\InfoAction;
use App\Modules\Telegram\Actions\Request\MessagesAction;
use App\Modules\Telegram\Actions\Request\ParticipantsAction;
use App\Modules\Telegram\TelegramService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TelegramServicePublicAccessTest extends TestCase
{
    public function test_private_peer_ids_never_reach_telegram_actions(): void
    {
        $this->mock(InfoAction::class)->shouldNotReceive('execute');
        $this->mock(MessagesAction::class)->shouldNotReceive('execute');
        $this->mock(ParticipantsAction::class)->shouldNotReceive('execute');
        $this->mock(CommentsAction::class)->shouldNotReceive('execute');
        $gateway = app(TelegramService::class);

        $this->assertNull($gateway->getInfo('-100123456789'));
        $this->assertNull($gateway->getMessages(['peer' => '-100123456789']));
        $this->assertNull($gateway->getParticipants(['chatUsername' => '-100123456789']));
        $this->assertFalse($gateway->getComments('-100123456789', 1)['ok']);
        $this->assertNull($gateway->getMessageMedia('-100123456789', 1));
    }

    public function test_saved_and_private_author_peers_are_not_forwarded_to_a_shared_session(): void
    {
        $this->mock(MessagesAction::class)->shouldNotReceive('execute');
        $gateway = app(TelegramService::class);

        $this->assertNull($gateway->getMessages(['peer' => '@publicgroup', 'saved_peer_id' => 'me']));
        $this->assertNull($gateway->getMessages(['peer' => '@publicgroup', 'from_id' => ['_' => 'inputPeerUser', 'user_id' => 123, 'access_hash' => 456]]));
    }

    public function test_a_user_profile_cannot_be_returned_as_public_channel_information(): void
    {
        $this->mock(InfoAction::class)->shouldReceive('execute')->once()->andReturn(['type' => 'user', 'User' => ['id' => 123]]);

        $this->assertNull(app(TelegramService::class)->getInfo('@username'));
    }

    public function test_public_channel_metadata_excludes_shared_session_privileges_and_private_data(): void
    {
        $this->mock(InfoAction::class)->shouldReceive('execute')->once()->andReturn([
            'type' => 'channel', 'channel_id' => 55,
            'Chat' => ['_' => 'channel', 'id' => 55, 'title' => 'Public channel', 'username' => 'channel',
                'access_hash' => 123, 'creator' => true, 'admin_rights' => ['ban_users' => true]],
            'full' => ['id' => 55, 'about' => 'Public description', 'participants_count' => 10,
                'exported_invite' => ['link' => 'private-invite'], 'linked_chat_id' => 777, 'read_inbox_max_id' => 99],
            'users' => [['id' => 777, 'phone' => 'private-phone']],
        ]);

        $info = app(TelegramService::class)->getInfo('@channel');

        $this->assertSame(['type', 'channel_id', 'Chat', 'full'], array_keys($info->raw));
        $this->assertSame(['_' => 'channel', 'id' => 55, 'title' => 'Public channel', 'username' => 'channel'], $info->raw['Chat']);
        $this->assertSame(['id' => 55, 'about' => 'Public description', 'participants_count' => 10], $info->raw['full']);
    }

    public function test_public_channels_do_not_authorize_member_list_access_via_shared_session(): void
    {
        $this->mock(ParticipantsAction::class)->shouldNotReceive('execute');

        $this->assertNull(app(TelegramService::class)->getParticipants(['chatUsername' => '@publicgroup']));
        $this->assertNull((new ParticipantsAction)->execute(['channel' => '@publicgroup']));
    }

    public function test_nested_photo_capabilities_are_not_exposed_in_public_metadata(): void
    {
        $photo = ['_' => 'photo', 'id' => 10, 'access_hash' => 123, 'file_reference' => 'opaque-reference',
            'sizes' => [['_' => 'photoSize', 'location' => ['access_hash' => 456, 'file_reference' => 'nested-reference', 'w' => 20]]]];
        $this->mock(InfoAction::class)->shouldReceive('execute')->once()->andReturn([
            'type' => 'channel', 'Chat' => ['_' => 'channel', 'id' => 55, 'photo' => $photo],
            'full' => ['chat_photo' => $photo],
        ]);

        $info = app(TelegramService::class)->getInfo('@channel');

        $expected = ['_' => 'photo', 'id' => 10, 'sizes' => [['_' => 'photoSize', 'location' => ['w' => 20]]]];
        $this->assertSame($expected, $info->chat->photo->photo);
        $this->assertSame($expected, $info->full->chat_photo->photo);
        $this->assertSame($expected, $info->raw['Chat']['photo']);
        $this->assertSame($expected, $info->raw['full']['chat_photo']);
    }

    public static function malformedMessages(): array
    {
        return [
            'missing messages' => [['_' => 'messages.channelMessages']],
            'null messages' => [['_' => 'messages.channelMessages', 'messages' => null]],
            'string messages' => [['_' => 'messages.channelMessages', 'messages' => 'invalid']],
            'associative messages' => [['_' => 'messages.channelMessages', 'messages' => ['post' => ['id' => 1]]]],
            'invalid item' => [['_' => 'messages.channelMessages', 'messages' => ['invalid']]],
            'missing item id' => [['_' => 'messages.channelMessages', 'messages' => [['message' => 'invalid']]]],
            'unknown response type' => [['_' => 'unknown', 'messages' => []]],
        ];
    }

    #[DataProvider('malformedMessages')]
    public function test_malformed_message_pages_cannot_be_mistaken_for_success(array $response): void
    {
        $this->mock(MessagesAction::class)->shouldReceive('execute')->once()->andReturn($response);

        $this->assertNull(app(TelegramService::class)->getMessages(['peer' => '@channel']));
    }

    public function test_a_valid_empty_message_page_remains_a_successful_result(): void
    {
        $this->mock(MessagesAction::class)->shouldReceive('execute')->once()->andReturn(['_' => 'messages.channelMessages', 'messages' => []]);

        $page = app(TelegramService::class)->getMessages(['peer' => '@channel']);

        $this->assertNotNull($page);
        $this->assertSame([], $page->messages);
    }

    public static function failedComments(): array
    {
        return [[[]], [[['error' => 'source_unavailable', 'comments' => []]]], [[['comments' => null]]]];
    }

    #[DataProvider('failedComments')]
    public function test_failed_comments_are_not_reported_as_successful_empty_pages(array $result): void
    {
        $this->mock(CommentsAction::class)->shouldReceive('execute')->once()->andReturn($result);

        $page = app(TelegramService::class)->getComments('@publicgroup', 1);

        $this->assertSame(['ok' => false, 'items' => [], 'nextOffsetId' => null, 'hasMore' => false, 'total' => 0], $page);
    }
}

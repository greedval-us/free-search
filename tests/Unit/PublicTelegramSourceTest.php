<?php

namespace Tests\Unit;

use App\Modules\Telegram\Access\PublicTelegramSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PublicTelegramSourceTest extends TestCase
{
    public static function privateIdentifiers(): array
    {
        return array_map(static fn (string $id): array => [$id], [
            '-100123456789', '123456789', '+79990000000', 'me', 'self',
            'https://t.me/+invite', 't.me/joinchat/invite', 'tg://resolve?domain=channel',
            'channel/123', '@@channel', 'first second',
        ]);
    }

    #[DataProvider('privateIdentifiers')]
    public function test_non_public_identifiers_never_reach_the_session_resolver(string $identifier): void
    {
        $result = PublicTelegramSource::resolve($identifier, fn () => $this->fail('Private identifiers must not reach Telegram.'));

        $this->assertNull($result);
    }

    public static function privateResolvedPeers(): array
    {
        return [
            'user profile' => [['peer' => ['_' => 'peerUser', 'user_id' => 55]]],
            'basic group' => [['peer' => ['_' => 'peerChat', 'chat_id' => 55]]],
            'private channel' => [['chats' => [['username' => null]]]],
            'removed username' => [['chats' => [['username' => 'renamed']]]],
            'forbidden channel' => [['chats' => [['_' => 'channelForbidden']]]],
            'untrusted partial peer' => [['chats' => [['min' => true]]]],
            'protected content' => [['chats' => [['noforwards' => true]]]],
            'wrong resolved channel' => [['peer' => ['channel_id' => 99]]],
        ];
    }

    #[DataProvider('privateResolvedPeers')]
    public function test_session_access_does_not_authorize_a_non_public_peer(array $override): void
    {
        $response = array_replace_recursive($this->channel(), $override);

        $result = PublicTelegramSource::resolve('@channel', fn () => $response);

        $this->assertNull($result);
    }

    public function test_it_pins_a_public_username_to_the_live_resolved_peer(): void
    {
        $calls = [];
        $resolve = function (string $username) use (&$calls): array {
            $calls[] = $username;

            return $this->channel();
        };

        $result = PublicTelegramSource::resolve('@CHANNEL', $resolve);
        PublicTelegramSource::resolve('@CHANNEL', $resolve);

        $this->assertSame(['channel', 'channel'], $calls);
        $this->assertSame(['_' => 'inputPeerChannel', 'channel_id' => 55, 'access_hash' => 123], $result['peer']);
    }

    public function test_it_accepts_an_active_public_alias_and_rejects_a_revoked_alias(): void
    {
        $response = $this->channel();
        $response['chats'][0]['usernames'] = [['username' => 'alias', 'active' => true]];

        $this->assertSame(55, PublicTelegramSource::resolve('alias', fn () => $response)['id']);
        $response['chats'][0]['usernames'][0]['active'] = false;
        $this->assertNull(PublicTelegramSource::resolve('alias', fn () => $response));
    }

    public static function nativePublicChannelTypes(): array
    {
        return ['broadcast channel' => ['channel'], 'public supergroup' => ['supergroup']];
    }

    #[DataProvider('nativePublicChannelTypes')]
    public function test_native_madeline_metadata_pins_the_public_channel_to_its_mtproto_id(string $type): void
    {
        $response = $this->nativeChannel($type);

        $result = PublicTelegramSource::resolve('@CHANNEL', static fn (): array => $response);

        $this->assertNotNull($result);
        $this->assertSame('channel', $result['username']);
        $this->assertSame(55, $result['id']);
        $this->assertSame(['_' => 'inputPeerChannel', 'channel_id' => 55, 'access_hash' => 123], $result['peer']);
        $this->assertSame(['_' => 'inputChannel', 'channel_id' => 55, 'access_hash' => 123], $result['channel']);
    }

    #[DataProvider('nativePublicChannelTypes')]
    public function test_native_madeline_metadata_accepts_an_active_public_alias(string $type): void
    {
        $response = $this->nativeChannel($type);
        $response['Chat']['usernames'] = [['username' => 'alias', 'active' => true]];

        $result = PublicTelegramSource::resolve('@ALIAS', static fn (): array => $response);

        $this->assertNotNull($result);
        $this->assertSame('alias', $result['username']);
        $this->assertSame(55, $result['id']);
    }

    #[DataProvider('nativePublicChannelTypes')]
    public function test_native_madeline_metadata_rejects_a_revoked_public_alias(string $type): void
    {
        $response = $this->nativeChannel($type);
        $response['Chat']['usernames'] = [['username' => 'alias', 'active' => false]];

        $result = PublicTelegramSource::resolve('alias', static fn (): array => $response);

        $this->assertNull($result);
    }

    public static function unsafeNativeChannelMetadata(): array
    {
        return [
            'user profile' => [['type' => 'user']],
            'basic group' => [['type' => 'chat']],
            'unknown peer type' => [['type' => 'unknown']],
            'forbidden channel' => [['Chat' => ['_' => 'channelForbidden']]],
            'mismatched channel id' => [['channel_id' => -1000000000099]],
            'mismatched bot api id' => [['bot_api_id' => -1000000000099]],
            'mismatched chat id' => [['Chat' => ['id' => -1000000000099]]],
            'basic group dialog id' => [['channel_id' => -55, 'bot_api_id' => -55, 'Chat' => ['id' => -55]]],
            'private channel' => [['Chat' => ['username' => null]]],
            'removed public username' => [['Chat' => ['username' => 'renamed']]],
            'missing access hash' => [['Chat' => ['access_hash' => null]]],
            'untrusted partial peer' => [['Chat' => ['min' => true]]],
            'restricted content' => [['Chat' => ['restricted' => true]]],
            'protected content' => [['Chat' => ['noforwards' => true]]],
            'neither broadcast nor megagroup' => [['Chat' => ['broadcast' => false, 'megagroup' => false]]],
        ];
    }

    #[DataProvider('unsafeNativeChannelMetadata')]
    public function test_native_madeline_metadata_does_not_authorize_an_unsafe_source(array $override): void
    {
        $response = array_replace_recursive($this->nativeChannel(), $override);

        $result = PublicTelegramSource::resolve('@channel', static fn (): array => $response);

        $this->assertNull($result);
    }

    public static function messagePeerFormats(): array
    {
        return [
            'native channel dialog id' => [-1000000000055, true],
            'native string channel dialog id' => ['-1000000000055', true],
            'raw channel peer' => [['_' => 'peerChannel', 'channel_id' => 55], true],
            'positive user id' => [55, false],
            'raw user peer' => [['_' => 'peerUser', 'user_id' => 55], false],
            'another channel dialog id' => [-1000000000077, false],
            'another raw channel peer' => [['_' => 'peerChannel', 'channel_id' => 77], false],
            'malformed peer array' => [['channel_id' => 55], false],
            'non numeric peer' => ['channel', false],
            'missing peer' => [null, false],
        ];
    }

    #[DataProvider('messagePeerFormats')]
    public function test_message_peer_formats_are_pinned_to_the_authorized_channel(mixed $peer, bool $matches): void
    {
        $this->assertSame($matches, PublicTelegramSource::matchesPeer($peer, 55));
    }

    private function nativeChannel(string $type = 'channel'): array
    {
        return [
            'type' => $type,
            'channel_id' => -1000000000055,
            'bot_api_id' => -1000000000055,
            'Chat' => ['_' => 'channel', 'id' => -1000000000055, 'access_hash' => 123,
                'username' => 'channel', 'broadcast' => $type === 'channel', 'megagroup' => $type === 'supergroup'],
        ];
    }

    private function channel(): array
    {
        return ['peer' => ['_' => 'peerChannel', 'channel_id' => 55],
            'chats' => [['_' => 'channel', 'id' => 55, 'access_hash' => 123, 'username' => 'channel', 'broadcast' => true]]];
    }
}

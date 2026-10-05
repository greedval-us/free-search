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

    private function channel(): array
    {
        return ['peer' => ['_' => 'peerChannel', 'channel_id' => 55],
            'chats' => [['_' => 'channel', 'id' => 55, 'access_hash' => 123, 'username' => 'channel', 'broadcast' => true]]];
    }
}

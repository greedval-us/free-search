<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface;
use App\Modules\Mastodon\Parser\MastodonParserCollector;
use App\Modules\Mastodon\Parser\MastodonParserRunStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class MastodonParserCollectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_pages_keep_comment_roots_after_a_checkpoint_is_reloaded(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $store = app(MastodonParserRunStore::class);
        $run = $store->create($user->id, ['account' => 'analyst@mastodon.social']);
        $gateway = $this->mock(MastodonGatewayInterface::class);
        $gateway->shouldReceive('lookupAccount')->once()->with('analyst@mastodon.social')
            ->andReturn(['id' => '42', 'statuses_count' => 2]);
        $gateway->shouldReceive('accountStatuses')->once()->with('42', 20, null)
            ->andReturn(['items' => [$this->statusPayload('300', 1)], 'pagination' => ['nextMaxId' => '300']]);
        $gateway->shouldReceive('accountStatuses')->once()->with('42', 20, '300')
            ->andReturn(['items' => [$this->statusPayload('300', 1), $this->statusPayload('200', 1)], 'pagination' => ['nextMaxId' => '200']]);
        $gateway->shouldReceive('accountStatuses')->once()->with('42', 20, '200')
            ->andReturn(['items' => [], 'pagination' => ['nextMaxId' => null]]);
        $gateway->shouldReceive('context')->once()->with('300')
            ->andReturn(['descendants' => [$this->statusPayload('301')]]);
        $gateway->shouldReceive('context')->once()->with('200')
            ->andReturn(['descendants' => [$this->statusPayload('201')]]);

        $firstPage = $this->advanceStored($store, $user->id, $run['runId']);
        $this->assertSame(['300'], $firstPage['cursor']['commentStatusIds']);
        for ($step = 0; $step < 5; $step++) {
            $finished = $this->advanceStored($store, $user->id, $run['runId']);
        }

        $this->assertSame('completed', $finished['status']);
        $this->assertSame(2, $finished['result']['statusesCount']);
        $this->assertSame(2, $finished['result']['commentsCount']);
        $this->assertSame(['300', '200'], array_column($finished['result']['statusesIndex'], 'id'));
        $this->assertSame(['300', '200'], array_column($finished['result']['commentsIndex'], 'rootStatusId'));
    }

    public function test_older_checkpoint_recovers_comment_roots_that_were_not_saved(): void
    {
        $gateway = $this->mock(MastodonGatewayInterface::class);
        $gateway->shouldReceive('accountStatuses')->once()->with('42', 20, '300')
            ->andReturn(['items' => [$this->statusPayload('200', 1)], 'pagination' => ['nextMaxId' => null]]);
        $run = $this->checkpoint('300');
        $run['data']['statusesIndex'] = [['id' => '300', 'postType' => 'original', 'repliesCount' => 1]];
        $run['data']['statusIds'] = ['300' => true];

        $next = app(MastodonParserCollector::class)->advance($run);

        $this->assertSame('comments', $next['stage']);
        $this->assertSame(['300', '200'], $next['cursor']['commentStatusIds']);
    }

    #[DataProvider('stalledPages')]
    public function test_stalled_pagination_fails_instead_of_looping_or_reporting_completion(?string $current, array $seen, array $items, string $nextCursor): void
    {
        $gateway = $this->mock(MastodonGatewayInterface::class);
        $gateway->shouldReceive('accountStatuses')->once()->with('42', 20, $current)
            ->andReturn(['items' => $items, 'pagination' => ['nextMaxId' => $nextCursor]]);
        $run = $this->checkpoint($current);
        $run['cursor']['statusesSeenMaxIds'] = $seen;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mastodon statuses pagination did not advance.');

        app(MastodonParserCollector::class)->advance($run);
    }

    public static function stalledPages(): array
    {
        return [
            'repeated cursor' => ['100', [], [['id' => '50']], '100'],
            'cursor cycle' => ['50', ['100'], [['id' => '25']], '100'],
            'empty page with a continuation' => [null, [], [], '100'],
        ];
    }

    public function test_comment_retry_reads_the_same_root_from_the_saved_checkpoint(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $store = app(MastodonParserRunStore::class);
        $run = $store->create($user->id, ['account' => 'analyst@mastodon.social']);
        $run['stage'] = 'comments';
        $run['cursor']['commentStatusIds'] = ['300'];
        $store->write($user->id, $run['runId'], $run);
        $gateway = $this->mock(MastodonGatewayInterface::class);
        $gateway->shouldReceive('context')->once()->with('300')->andThrow(new RuntimeException('Temporary failure'));
        $gateway->shouldReceive('context')->once()->with('300')->andReturn(['descendants' => [$this->statusPayload('301')]]);

        try {
            $this->advanceStored($store, $user->id, $run['runId']);
            $this->fail('A failed upstream request must not advance the checkpoint.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Temporary failure', $exception->getMessage());
        }
        $saved = $store->get($user->id, $run['runId']);
        $retried = $this->advanceStored($store, $user->id, $run['runId']);

        $this->assertSame(0, $saved['cursor']['commentStatusIndex']);
        $this->assertSame(1, $retried['cursor']['commentStatusIndex']);
        $this->assertSame('finishing', $retried['stage']);
        $this->assertSame(['301'], array_column($retried['data']['commentsIndex'], 'commentId'));
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_upstream_payload_does_not_mark_a_collection_as_finished(string $stage, string $method, array $payload, string $error): void
    {
        $gateway = $this->mock(MastodonGatewayInterface::class);
        $gateway->shouldReceive($method)->once()->andReturn($payload);
        $run = $this->checkpoint(null);
        $run['stage'] = $stage;
        $run['cursor']['commentStatusIds'] = ['300'];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($error);

        app(MastodonParserCollector::class)->advance($run);
    }

    public static function invalidPayloads(): array
    {
        return [
            'statuses' => ['statuses', 'accountStatuses', ['items' => null], 'Mastodon returned an invalid statuses page.'],
            'comments' => ['comments', 'context', ['descendants' => null], 'Mastodon returned an invalid comments context.'],
        ];
    }

    public function test_unresolved_account_never_requests_statuses_with_an_empty_id(): void
    {
        $gateway = $this->mock(MastodonGatewayInterface::class);
        $gateway->shouldReceive('lookupAccount')->once()->andReturn([]);
        $gateway->shouldNotReceive('accountStatuses');
        $run = $this->checkpoint(null);
        $run['data']['account'] = null;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mastodon account could not be resolved.');

        app(MastodonParserCollector::class)->advance($run);
    }

    private function advanceStored(MastodonParserRunStore $store, int $userId, string $runId): array
    {
        $advanced = app(MastodonParserCollector::class)->advance($store->get($userId, $runId));
        $store->write($userId, $runId, $advanced);

        return $store->get($userId, $runId);
    }

    private function checkpoint(?string $maxId): array
    {
        return [
            'status' => 'running',
            'stage' => 'statuses',
            'context' => ['account' => 'analyst@mastodon.social'],
            'cursor' => ['statusesMaxId' => $maxId, 'commentStatusIds' => []],
            'data' => ['account' => ['id' => '42']],
        ];
    }

    private function statusPayload(string $id, int $replies = 0): array
    {
        return ['id' => $id, 'content' => '<p>Public status</p>', 'replies_count' => $replies];
    }
}

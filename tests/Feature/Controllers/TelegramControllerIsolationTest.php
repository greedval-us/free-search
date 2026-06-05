<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsRangeResolverInterface;
use App\Modules\Telegram\DTO\Request\SearchCommentsQueryDTO;
use App\Modules\Telegram\DTO\Request\SearchMessagesQueryDTO;
use App\Modules\Telegram\DTO\Request\TelegramAnalyticsParamsDTO;
use App\Modules\Telegram\DTO\Request\TelegramParserStartDTO;
use App\Modules\Telegram\DTO\Result\AnalyticsSummaryResultDTO;
use App\Modules\Telegram\DTO\Result\SearchCommentsResultDTO;
use App\Modules\Telegram\DTO\Result\SearchMessagesResultDTO;
use App\Modules\Telegram\DTO\Result\TelegramParserRunStatusDTO;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use App\Modules\Telegram\Search\Contracts\TelegramSearchApplicationServiceInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Feature\Controllers\Concerns\CreatesPaidUser;
use Tests\Feature\Controllers\Concerns\MocksTelegramServices;
use Tests\TestCase;

class TelegramControllerIsolationTest extends TestCase
{
    use CreatesPaidUser;
    use MocksTelegramServices;
    use RefreshDatabase;

    public function test_telegram_search_messages_controller_returns_dto_payload(): void
    {
        $user = User::factory()->create();

        $this->mockTelegramControllerBaseDeps();
        $this->mock(TelegramSearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('messages')
                ->once()
                ->with(Mockery::on(
                    fn (SearchMessagesQueryDTO $query): bool => $query->chatUsername === 'channel'
                        && $query->limit === 7
                        && $query->offsetId === 12
                        && $query->filter['peer'] === '@channel'
                ))
                ->andReturn(new SearchMessagesResultDTO(
                    ok: true,
                    items: [['id' => 100, 'text' => 'hello']],
                    pagination: [
                        'limit' => 7,
                        'offsetId' => 12,
                        'nextOffsetId' => null,
                        'hasMore' => false,
                        'total' => 1,
                    ],
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('telegram.search.messages', [
                'chatUsername' => '@channel',
                'q' => 'hello',
                'limit' => 7,
                'offsetId' => 12,
            ]))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('items.0.id', 100)
            ->assertJsonPath('pagination.limit', 7);
    }

    public function test_telegram_search_comments_controller_returns_dto_payload(): void
    {
        $user = User::factory()->create();

        $this->mockTelegramControllerBaseDeps();
        $this->mock(TelegramSearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('comments')
                ->once()
                ->with(Mockery::on(
                    fn (SearchCommentsQueryDTO $query): bool => $query->chatUsername === 'channel'
                        && $query->postId === 42
                        && $query->limit === 5
                ))
                ->andReturn(new SearchCommentsResultDTO([
                    'ok' => true,
                    'items' => [['id' => 501, 'text' => 'comment']],
                ]));
        });

        $this
            ->actingAs($user)
            ->getJson(route('telegram.search.comments', [
                'chatUsername' => '@channel',
                'postId' => 42,
                'limit' => 5,
            ]))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('items.0.id', 501);
    }

    public function test_telegram_analytics_summary_controller_uses_range_resolver_and_service(): void
    {
        $user = $this->paidUser();
        $from = Carbon::parse('2026-05-01 00:00:00');
        $to = Carbon::parse('2026-05-07 23:59:59');

        $this->mockTelegramAnalyticsRange($from, $to);
        $this->mock(TelegramAnalyticsApplicationServiceInterface::class, function ($mock) use ($from, $to): void {
            $mock->shouldReceive('buildSummary')
                ->once()
                ->with(
                    Mockery::on(
                        fn (TelegramAnalyticsParamsDTO $params): bool => $params->chatUsername === 'channel'
                            && $params->scorePriority === 'reach'
                            && $params->keyword === 'risk'
                    ),
                    $from,
                    $to,
                    'previous'
                )
                ->andReturn(new AnalyticsSummaryResultDTO([
                    'chatUsername' => 'channel',
                    'messages' => 15,
                ]));
        });

        $this
            ->actingAs($user)
            ->getJson(route('telegram.analytics.summary', [
                'chatUsername' => '@channel',
                'scorePriority' => 'reach',
                'keyword' => 'risk',
                'snapshotRole' => 'previous',
            ]))
            ->assertOk()
            ->assertJsonPath('data.chatUsername', 'channel')
            ->assertJsonPath('data.messages', 15);
    }

    public function test_telegram_parser_start_controller_passes_authenticated_user_to_service(): void
    {
        $user = $this->paidUser();

        $this->mock(TelegramParserApplicationServiceInterface::class, function ($mock) use ($user): void {
            $mock->shouldReceive('start')
                ->once()
                ->with(Mockery::on(
                    fn (TelegramParserStartDTO $input): bool => $input->userId === $user->id
                        && $input->chatUsername === 'channel'
                        && $input->period === 'week'
                        && $input->keyword === 'risk'
                ))
                ->andReturn(new TelegramParserRunStatusDTO([
                    'ok' => true,
                    'runId' => 'tg-run-1',
                    'status' => 'queued',
                ]));
        });

        $this
            ->actingAs($user)
            ->postJson(route('telegram.parser.start'), [
                'chatUsername' => '@channel',
                'period' => 'week',
                'keyword' => 'risk',
            ])
            ->assertOk()
            ->assertJsonPath('runId', 'tg-run-1')
            ->assertJsonPath('status', 'queued');
    }

    public function test_telegram_parser_status_controller_returns_not_found_for_missing_run(): void
    {
        $user = $this->paidUser();

        $this->mock(TelegramParserApplicationServiceInterface::class, function ($mock) use ($user): void {
            $mock->shouldReceive('status')->once()->with($user->id, 'missing-run')->andReturn(null);
        });

        $this
            ->actingAs($user)
            ->getJson(route('telegram.parser.status', ['runId' => 'missing-run']))
            ->assertNotFound();
    }
}

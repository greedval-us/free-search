<?php

namespace Tests\Feature;

use App\Models\FeatureUsageDaily;
use App\Models\ParserRun;
use App\Models\User;
use App\Modules\ParserSupport\Contracts\ParserRunJobDispatcherInterface;
use App\Modules\ParserSupport\ParserRunExecutionCoordinator;
use App\Modules\Telegram\Parser\TelegramParserRunStore;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\Feature\Concerns\CreatesSubscribedUser;
use Tests\TestCase;

class FeatureUsageReceiptTest extends TestCase
{
    use CreatesSubscribedUser;
    use RefreshDatabase;

    public function test_failed_response_after_midnight_refunds_the_original_application_day(): void
    {
        config()->set('app.timezone', 'Europe/Moscow');
        $this->travelTo(CarbonImmutable::parse('2026-10-10 23:59:59', 'Europe/Moscow'));
        $user = $this->createSubscribedUser();
        $this->registerFailingRoute($user, false);

        $this->actingAs($user)->getJson('/_quota-midnight')->assertUnprocessable();

        $this->assertUsage($user, '2026-10-10', 0);
        $this->assertUsage($user, '2026-10-11', 1);
    }

    public function test_exception_after_midnight_refunds_the_original_application_day(): void
    {
        config()->set('app.timezone', 'Europe/Moscow');
        $this->travelTo(CarbonImmutable::parse('2026-10-10 23:59:59', 'Europe/Moscow'));
        $user = $this->createSubscribedUser();
        $this->registerFailingRoute($user, true);

        $this->actingAs($user)->getJson('/_quota-midnight')->assertUnprocessable();

        $this->assertUsage($user, '2026-10-10', 0);
        $this->assertUsage($user, '2026-10-11', 1);
    }

    public function test_refunding_a_serialized_receipt_twice_preserves_another_operations_usage(): void
    {
        $user = $this->createSubscribedUser();
        $service = app(FeatureAccessServiceInterface::class);
        $first = $service->consumeResource($user, 'telegram.analytics');
        $service->consumeResource($user, 'telegram.analytics');
        $this->assertNotNull($first->receipt ?? null, 'A debit must return its refund context.');

        $service->refund($user, $first->receipt->id);
        $service->refund($user, unserialize(serialize($first->receipt))->id);

        $this->assertUsage($user, now(config('app.timezone'))->toDateString(), 1);
    }

    public function test_parser_dispatch_failure_after_midnight_refunds_only_the_original_debit(): void
    {
        Storage::fake('private');
        config()->set('app.timezone', 'Europe/Moscow');
        $this->travelTo(CarbonImmutable::parse('2026-10-10 23:59:59', 'Europe/Moscow'));
        $user = $this->createSubscribedUser();
        $runId = null;
        $this->mock(ParserRunJobDispatcherInterface::class)->shouldReceive('dispatch')->once()
            ->with('telegram', $user->id, Mockery::type('string'))
            ->andReturnUsing(function (string $module, int $userId, string $dispatchedRunId) use ($user, &$runId): never {
                $runId = $dispatchedRunId;
                $this->travelTo(CarbonImmutable::parse('2026-10-11 00:00:01', 'Europe/Moscow'));
                app(FeatureAccessServiceInterface::class)->consumeResource($user, 'telegram.parser');

                throw new RuntimeException('Synthetic dispatch outage');
            });

        try {
            app(ParserRunExecutionCoordinator::class)->start(app(TelegramParserRunStore::class), 'telegram', $user->id, []);
            $this->fail('The dispatcher failure should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic dispatch outage', $exception->getMessage());
        }

        $this->assertUsage($user, '2026-10-10', 0, 'telegram.parser');
        $this->assertUsage($user, '2026-10-11', 1, 'telegram.parser');
        $this->assertDatabaseHas('parser_runs', ['run_id' => $runId, 'status' => 'failed']);
        $this->assertSame(1, ParserRun::query()->where('user_id', $user->id)->count());
    }

    public function test_receipt_cannot_refund_a_different_users_usage(): void
    {
        $owner = $this->createSubscribedUser();
        $other = $this->createSubscribedUser();
        $service = app(FeatureAccessServiceInterface::class);
        $decision = $service->consumeResource($owner, 'telegram.analytics');
        $service->consumeResource($other, 'telegram.analytics');
        $this->assertNotNull($decision->receipt ?? null, 'A debit must return its refund context.');

        $service->refund($other, $decision->receipt->id);

        $this->assertUsage($owner, now(config('app.timezone'))->toDateString(), 1);
        $this->assertUsage($other, now(config('app.timezone'))->toDateString(), 1);
    }

    public function test_refund_at_zero_is_final_and_cannot_remove_a_later_debit(): void
    {
        $user = $this->createSubscribedUser();
        $service = app(FeatureAccessServiceInterface::class);
        $decision = $service->consumeResource($user, 'telegram.analytics');
        $this->assertNotNull($decision->receipt ?? null, 'A debit must return its refund context.');
        FeatureUsageDaily::query()->where('user_id', $user->id)->update(['used' => 0]);

        $service->refund($user, $decision->receipt->id);
        $this->assertUsage($user, now(config('app.timezone'))->toDateString(), 0);
        $service->consumeResource($user, 'telegram.analytics');
        $service->refund($user, $decision->receipt->id);

        $this->assertUsage($user, now(config('app.timezone'))->toDateString(), 1);
    }

    public function test_denied_inspected_non_counting_and_staff_decisions_have_no_refundable_debit(): void
    {
        $service = app(FeatureAccessServiceInterface::class);
        $user = User::factory()->create();
        $staff = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_ADMIN]);
        $service->consumeResource($user, 'telegram.analytics');

        foreach ([
            $service->consumeResource($user, 'telegram.analytics'),
            $service->inspect($user, 'telegram.analytics'),
            $service->consume($user, 'telegram.parser.status'),
            $service->consumeResource($staff, 'telegram.analytics'),
        ] as $decision) {
            $this->assertNull($decision->receipt ?? null);
            $service->refund($user, $decision->receipt?->id);
        }

        $this->assertUsage($user, now(config('app.timezone'))->toDateString(), 1);
        $this->assertDatabaseMissing('feature_usage_daily', ['user_id' => $staff->id]);
    }

    public function test_current_staff_bypass_keeps_a_counted_receipt_pending_until_the_user_is_ordinary_again(): void
    {
        $user = $this->createSubscribedUser();
        $ordinaryAccountType = $user->fresh()->account_type;
        $service = app(FeatureAccessServiceInterface::class);
        $decision = $service->consumeResource($user, 'telegram.analytics');
        $this->assertNotNull($decision->receipt);

        $user->forceFill(['account_type' => User::ACCOUNT_TYPE_ADMIN])->save();
        $service->refund($user, $decision->receipt->id);

        $this->assertUsage($user, now(config('app.timezone'))->toDateString(), 1);
        $this->assertDatabaseHas('feature_usage_receipts', ['id' => $decision->receipt->id, 'released_at' => null]);

        $user->forceFill(['account_type' => $ordinaryAccountType])->save();
        $service->refund($user, $decision->receipt->id);
        $service->consumeResource($user, 'telegram.analytics');
        $service->refund($user, $decision->receipt->id);

        $this->assertUsage($user, now(config('app.timezone'))->toDateString(), 1);
    }

    private function registerFailingRoute(User $user, bool $throws): void
    {
        Route::get('/_quota-midnight', function () use ($user, $throws) {
            $this->travelTo(CarbonImmutable::parse('2026-10-11 00:00:01', 'Europe/Moscow'));
            app(FeatureAccessServiceInterface::class)->consumeResource($user, 'telegram.analytics');

            if ($throws) {
                throw ValidationException::withMessages(['target' => ['Invalid target.']]);
            }

            return response()->json(['ok' => false], 422);
        })->middleware('feature.access')->name('quota-midnight');
        config()->set('access.protected_routes.quota-midnight', [
            'resource' => 'telegram.analytics', 'counts' => true,
        ]);
    }

    private function assertUsage(User $user, string $date, int $used, string $feature = 'telegram.analytics'): void
    {
        $this->assertSame($used, FeatureUsageDaily::query()
            ->where('user_id', $user->id)
            ->where('feature', $feature)
            ->whereDate('usage_date', $date)
            ->value('used'));
    }
}

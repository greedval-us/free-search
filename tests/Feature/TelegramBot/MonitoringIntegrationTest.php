<?php

namespace Tests\Feature\TelegramBot;

use App\Integrations\TelegramBot\MonitoringArtifactProvider;
use App\Integrations\TelegramBot\MonitoringDelivery;
use App\Models\MonitoringProject;
use App\Models\MonitoringReport;
use App\Models\MonitoringSchedule;
use App\Modules\TelegramBot\Application\ArtifactRegistry;
use App\Modules\TelegramBot\Application\BotRouter;
use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\Contracts\BotTransport;
use App\Modules\TelegramBot\Domain\DTO\BotContext;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Domain\Exceptions\TelegramTransportException;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Jobs\DeliverBotMessage;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;
use App\Services\Monitoring\MonitoringExports;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;

final class MonitoringIntegrationTest extends TelegramBotTestCase
{
    public function test_automatic_summary_uses_saved_version_and_outbox_without_automatic_files(): void
    {
        $this->freezeTime();
        $link = $this->linkedUser();
        $report = $this->report($link);
        $deliveryService = app(MonitoringDelivery::class);
        $deliveryService->enqueue($report);
        $deliveryService->enqueue($report);
        $delivery = BotDelivery::query()->sole();
        $this->assertSame('monitoring_digest', $delivery->kind);
        $this->assertSame((string) $report->id, $delivery->reference);
        $this->assertSame('pending', $report->fresh()->delivery_status);
        Queue::assertPushed(DeliverBotMessage::class, 1);
        $this->mock(BotTransport::class)->shouldReceive('message')->once()->with($link->telegraph_chat_id,
            \Mockery::on(function (BotScreen $screen) use ($report): bool {
                $this->assertStringContainsString('Saved digest headline', $screen->text);
                $this->assertStringContainsString('Original publication excerpt', $screen->text);
                $this->assertStringContainsString('https://t.me/publicchannel/12', $screen->text);
                $this->assertStringContainsString('Collected publications: 1', $screen->text);
                $this->assertStringNotContainsString('never-show-this-secret', $screen->text);
                $this->assertSame('https://example.test/monitoring/reports/'.$report->id, $screen->buttons[0]->value);
                $this->assertSame(['k' => 'monitoring_document', 'i' => $report->id, 'f' => 'xlsx'], $screen->buttons[1]->parameters);

                return true;
            }));
        $job = new DeliverBotMessage($delivery->id, $link->telegram_id);
        app()->call([$job, 'handle']);
        app()->call([$job, 'handle']);
        $deliveryService->enqueue($report);
        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        $this->assertSame(BotDelivery::SENT, $report->fresh()->delivery_status);
        $this->assertDatabaseCount('telegram_bot_deliveries', 1);
        $this->assertDatabaseCount('telegram_bot_reports', 0);
    }

    #[DataProvider('consentUnavailable')]
    public function test_saved_reports_remain_available_when_delivery_cannot_be_queued(string $reason): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        match ($reason) {
            'project opt out' => $report->project->update(['delivery_enabled' => false]),
            'schedule opt out' => $report->schedule->update(['delivery_enabled' => false]),
            'notifications opt out' => $link->update(['notifications_enabled' => false]),
            'disabled bot' => config(['telegram_bot.enabled' => false]),
            'empty skip policy' => $report->update(['status' => 'empty']),
        };
        if ($reason === 'empty skip policy') {
            $report->project->update(['empty_delivery' => 'skip']);
        }
        app(MonitoringDelivery::class)->enqueue($report);
        $this->assertSame('disabled', $report->fresh()->delivery_status);
        $this->assertContains($report->fresh()->status, ['completed', 'empty']);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        Queue::assertNothingPushed();
        Telegraph::assertNothingSent();
    }

    public static function consentUnavailable(): array
    {
        return [['project opt out'], ['schedule opt out'], ['notifications opt out'], ['disabled bot'], ['empty skip policy']];
    }

    public function test_unlinked_report_is_saved_and_not_replayed_when_linked_later(): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        $attributes = $link->getAttributes();
        $link->delete();
        app(MonitoringDelivery::class)->enqueue($report);
        $this->assertSame('not_linked', $report->fresh()->delivery_status);
        BotLink::query()->create(collect($attributes)->except(['id', 'created_at', 'updated_at'])->all());
        app(MonitoringDelivery::class)->enqueue($report);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        $this->assertSame('completed', $report->fresh()->status);
        Queue::assertNothingPushed();
    }

    #[DataProvider('revocations')]
    public function test_revoking_access_or_changing_configuration_before_job_prevents_summary(string $reason): void
    {
        $this->freezeTime();
        $link = $this->linkedUser();
        $report = $this->report($link);
        app(MonitoringDelivery::class)->enqueue($report);
        $delivery = BotDelivery::query()->sole();
        match ($reason) {
            'project paused' => $report->project->update(['status' => 'paused']),
            'project archived' => $report->project->update(['status' => 'archived']),
            'project opt out' => $report->project->update(['delivery_enabled' => false]),
            'project edited' => $report->project->update(['generation' => 2]),
            'schedule disabled' => $report->schedule->update(['enabled' => false]),
            'schedule opt out' => $report->schedule->update(['delivery_enabled' => false]),
            'schedule edited' => $report->schedule->update(['generation' => 2]),
            'notifications opt out' => $link->update(['notifications_enabled' => false]),
            'blocked user' => $link->user->update(['is_blocked' => true]),
            'unverified user' => $link->user->forceFill(['email_verified_at' => null])->save(),
            'changed telegram id' => $link->user->update(['telegram_id' => '10002']),
            'report expired' => $report->update(['expires_at' => now()->subSecond()]),
        };
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
        $this->assertSame(BotDelivery::SKIPPED, $delivery->fresh()->status);
        $this->assertSame(BotDelivery::SKIPPED, $report->fresh()->delivery_status);
        $this->assertSame('completed', $report->fresh()->status);
        Telegraph::assertNothingSent();
    }

    public static function revocations(): array
    {
        return array_map(fn ($reason) => [$reason], [
            'project paused', 'project archived', 'project opt out', 'project edited', 'schedule disabled', 'schedule opt out',
            'schedule edited', 'notifications opt out', 'blocked user', 'unverified user', 'changed telegram id', 'report expired',
        ]);
    }

    public function test_empty_summary_is_sent_only_with_explicit_send_policy(): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link, ['status' => 'empty', 'summary' => ['title' => 'Empty period', 'introduction' => '', 'count' => 0, 'bullets' => []]]);
        app(MonitoringDelivery::class)->enqueue($report);
        $delivery = BotDelivery::query()->sole();
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
        Telegraph::assertSentData('sendMessage', ['chat_id' => $link->telegram_id], false);
        $this->assertSame('sent', $report->fresh()->delivery_status);
    }

    public function test_optional_automatic_file_requires_separate_opt_in_and_uses_saved_export(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        $report->project->update(['attach_files' => true]);
        app(MonitoringDelivery::class)->enqueue($report);
        $this->assertDatabaseCount('telegram_bot_deliveries', 2);
        $delivery = BotDelivery::query()->where('kind', 'monitoring_document')->sole();
        $this->assertSame('xlsx', $delivery->payload['format']);
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertSame('pending', $report->fresh()->delivery_status);
        Telegraph::assertSentData('sendDocument', ['chat_id' => $link->telegram_id], false);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }

    public function test_file_consent_does_not_enable_project_attachments(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => false]);
        $report = $this->report($link);
        $report->project->update(['attach_files' => true]);
        app(MonitoringDelivery::class)->enqueue($report);
        $this->assertSame('monitoring_digest', BotDelivery::query()->sole()->kind);
        Queue::assertPushed(DeliverBotMessage::class, 1);
    }

    public function test_document_rechecks_project_consent_after_slow_render_and_removes_temporary_file(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        $report->project->update(['attach_files' => true]);
        app(MonitoringDelivery::class)->enqueue($report);
        $delivery = BotDelivery::query()->where('kind', 'monitoring_document')->sole();
        $provider = $this->mock(ArtifactProvider::class);
        $provider->shouldReceive('key')->andReturn('monitoring_document');
        $provider->shouldReceive('document')->once()->andReturnUsing(function () use ($report): BotDocument {
            $document = app(TemporaryDocuments::class)->create('report.xlsx', fn ($path) => Storage::disk('local')->put($path, 'test'));
            $report->project->update(['delivery_enabled' => false]);

            return $document;
        });
        $this->instance(ArtifactRegistry::class, new ArtifactRegistry([$provider]));
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
        $this->assertSame('skipped', $delivery->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
        Telegraph::assertNothingSent();
    }

    public function test_manual_historical_json_and_xlsx_use_retained_snapshot_without_generation_quota(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => false, 'notifications_enabled' => false]);
        $report = $this->report($link);
        $report->project->update(['name' => 'Changed name', 'status' => 'archived', 'generation' => 2, 'delivery_enabled' => false]);
        $provider = app(MonitoringArtifactProvider::class);
        $this->assertSame($report->id, $provider->listing($link->user_id, 1)->items()[0]['id']);
        $json = $provider->document($link->user_id, $report->id, 'json', 'en');
        $data = json_decode(file_get_contents($json->path), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('Saved digest headline', json_encode($data));
        $this->assertStringContainsString('Original publication excerpt', json_encode($data));
        app(TemporaryDocuments::class)->remove($json);
        $xlsx = $provider->document($link->user_id, $report->id, 'xlsx', 'en');
        $workbook = IOFactory::load($xlsx->path);
        $this->assertGreaterThanOrEqual(2, $workbook->getSheetCount());
        $workbook->disconnectWorksheets();
        app(TemporaryDocuments::class)->remove($xlsx);
        $this->assertTrue(app(DeliveryOutbox::class)->enqueue($link, 'monitoring_document', (string) $report->id, ['format' => 'json'], false, 'manual-report'));
        $delivery = BotDelivery::query()->sole();
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertNull($report->fresh()->delivery_status);
        $this->assertDatabaseCount('feature_usage_daily', 0);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }

    public function test_foreign_expired_and_unsupported_monitoring_downloads_are_denied(): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        $provider = app(MonitoringArtifactProvider::class);
        foreach ([[$link->user_id + 1, 'json'], [$link->user_id, '../env']] as [$userId, $format]) {
            try {
                $provider->document($userId, $report->id, $format, 'en');
                $this->fail('Unavailable report cannot be exported.');
            } catch (ArtifactUnavailable) {
                $this->addToAssertionCount(1);
            }
        }
        $report->update(['expires_at' => now()->subSecond()]);
        $this->assertCount(0, $provider->listing($link->user_id, 1)->items());
        $this->expectException(ArtifactUnavailable::class);
        $provider->document($link->user_id, $report->id, 'json', 'en');
    }

    public function test_projects_menu_hides_other_users_and_toggle_rejects_old_or_foreign_callback(): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        $other = $this->linkedUser('10002');
        $foreign = $this->report($other);
        $context = new BotContext($link->telegraph_chat_id, $link->telegram_id, 'en', 'monitor-menu', $link->id, $link->user_id);
        $router = app(BotRouter::class);
        $screen = $router->dispatch('monitor', $context);
        $projects = collect($screen->buttons)->where('type', 'action')->where('value', 'monitor');
        $this->assertCount(1, $projects);
        $this->assertSame(['i' => $report->project_id], $projects->first()->parameters);
        $router->dispatch('monitor', $context, ['i' => (string) $foreign->project_id, 'd' => '0', 'g' => '1']);
        $this->assertTrue($foreign->project->fresh()->delivery_enabled);
        $router->dispatch('monitor', $context, ['i' => (string) $report->project_id, 'd' => '0', 'g' => '1']);
        $this->assertFalse($report->project->fresh()->delivery_enabled);
        $this->assertSame(2, $report->project->fresh()->generation);
        $router->dispatch('monitor', $context, ['i' => (string) $report->project_id, 'd' => '1', 'g' => '1']);
        $this->assertFalse($report->project->fresh()->delivery_enabled);
        $this->expectException(ArtifactUnavailable::class);
        $router->dispatch('monitor', $context, ['r' => (string) $foreign->id]);
    }

    public function test_telegram_permanent_failure_changes_delivery_only(): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        app(MonitoringDelivery::class)->enqueue($report);
        $delivery = BotDelivery::query()->sole();
        $this->mock(BotTransport::class)->shouldReceive('message')->once()->andThrow(new TelegramTransportException(403));
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);
        $this->assertSame('failed', $report->fresh()->delivery_status);
        $this->assertSame('completed', $report->fresh()->status);
        $this->assertSame('telegram_403', $delivery->fresh()->error_code);
    }

    private function report(BotLink $link, array $attributes = []): MonitoringReport
    {
        $project = MonitoringProject::query()->create([
            'user_id' => $link->user_id, 'name' => 'Monitoring project', 'status' => 'active', 'generation' => 1,
            'delivery_enabled' => true, 'attach_files' => false, 'empty_delivery' => 'send',
            'mode' => 'overview', 'language' => 'en', 'timezone' => 'Europe/Moscow', 'filters' => [],
        ]);
        $schedule = MonitoringSchedule::query()->create([
            'project_id' => $project->id, 'period' => 'day', 'delivery_enabled' => true, 'enabled' => true,
            'time' => '09:00', 'timezone' => 'Europe/Moscow', 'anchor_date' => now()->toDateString(), 'next_run_at' => now()->addDay(), 'generation' => 1,
        ]);
        $report = MonitoringReport::query()->create([
            'user_id' => $link->user_id, 'project_id' => $project->id, 'schedule_id' => $schedule->id,
            'trigger_key' => 'bot-test:'.$project->id, 'version' => 1, 'status' => 'completed', 'period' => 'day',
            'start_at' => now()->subDay(), 'end_at' => now(), 'cutoff_at' => now(), 'timezone' => 'Europe/Moscow',
            'configuration' => ['generation' => 1, 'schedule_generation' => 1, 'name' => 'Saved project name', 'credential' => 'never-show-this-secret'],
            'summary' => ['title' => 'Saved digest headline', 'introduction' => 'Overview from saved materials', 'count' => 1,
                'bullets' => [['text' => 'Original publication excerpt', 'urls' => ['https://t.me/publicchannel/12']]], 'generator' => 'basic-v1'],
            'coverage' => [], 'expires_at' => now()->addDays(90), 'completed_at' => now(), 'delivery_status' => null,
            ...$attributes,
        ]);
        $report->items()->create(['snapshot' => [
            'platform' => 'telegram', 'external_id' => '12', 'title' => 'Original publication excerpt', 'text' => 'Original publication excerpt',
            'url' => 'https://t.me/publicchannel/12', 'published_at' => now()->subHour()->toIso8601String(), 'source_identity' => 'publicchannel',
            'source_title' => 'Public channel', 'collected_at' => now()->toIso8601String(), 'metrics' => [], 'author' => 'Author',
        ]]);
        $project->update(['delivery_enabled' => false]);
        app(MonitoringExports::class)->prepare($report->id, $link->user_id);
        $this->assertSame('ready', $report->fresh()->file_status);
        $project->update(['delivery_enabled' => true]);
        $report->refresh()->update(['delivery_status' => null]);

        return $report->fresh();
    }
}

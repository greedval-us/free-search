<?php

namespace Tests\Feature\TelegramBot;

use App\Integrations\TelegramBot\SiteIntelReportArtifactProvider;
use App\Integrations\TelegramBot\SiteIntelReportBotListener;
use App\Models\SiteIntelReportSchedule;
use App\Models\SiteIntelScheduledReport;
use App\Modules\SiteIntel\Application\Reports\Events\SiteIntelReportCompleted;
use App\Modules\TelegramBot\Application\ArtifactRegistry;
use App\Modules\TelegramBot\Application\BotRouter;
use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\Contracts\BotTransport;
use App\Modules\TelegramBot\Domain\DTO\BotContext;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Jobs\DeliverBotMessage;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

final class SiteIntelReportIntegrationTest extends TelegramBotTestCase
{
    #[DataProvider('reportTypes')]
    public function test_completed_report_is_automatically_sent_once_as_existing_html_and_temporary_file_is_removed(string $type, string $title): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link, ['report_type' => $type]);
        DB::transaction(fn () => event(new SiteIntelReportCompleted($report->id)));
        event(new SiteIntelReportCompleted($report->id));

        $this->assertDatabaseCount('telegram_bot_deliveries', 1);
        $delivery = BotDelivery::query()->sole();
        $this->assertSame('site_intel_report', $delivery->kind);
        $this->assertSame(['format' => 'html'], $delivery->payload);
        Queue::assertPushed(DeliverBotMessage::class, 1);
        $this->assertInstanceOf(SiteIntelReportArtifactProvider::class, app(ArtifactRegistry::class)->get('site_intel_report'));
        $this->mock(BotTransport::class)->shouldReceive('document')->once()->with($link->telegraph_chat_id,
            \Mockery::on(function (BotDocument $document) use ($report, $title): bool {
                $content = file_get_contents($document->path);

                return $document->filename === 'site-intel-report-'.$report->id.'.html'
                    && str_contains($content, $title)
                    && str_contains($content, 'example.com') && str_contains($content, '&lt;script&gt;private&lt;/script&gt;')
                    && ! str_contains($content, '<script>private</script>');
            }));

        $job = new DeliverBotMessage($delivery->id, $link->telegram_id);
        app()->call([$job, 'handle']);
        app()->call([$job, 'handle']);

        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }

    public static function reportTypes(): array
    {
        return ['analytics' => ['analytics', 'Site Intel Analytics Report'], 'SEO audit' => ['seo-audit', 'SEO Audit Report']];
    }

    #[DataProvider('automaticDeliveryPreferences')]
    public function test_automatic_report_requires_schedule_and_file_delivery_consent(bool $scheduleConsent, bool $fileConsent, bool $accepted): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => $fileConsent]);
        $report = $this->report($link, ['send_to_bot' => $scheduleConsent]);

        app(SiteIntelReportBotListener::class)->handle(new SiteIntelReportCompleted($report->id));

        $this->assertDatabaseCount('telegram_bot_deliveries', $accepted ? 1 : 0);
        Telegraph::assertNothingSent();
    }

    public static function automaticDeliveryPreferences(): array
    {
        return ['schedule opted out' => [false, true, false], 'file delivery opted out' => [true, false, false], 'both opted in' => [true, true, true]];
    }

    #[DataProvider('reportTypes')]
    public function test_manual_report_history_delivers_json_after_schedule_deletion_without_automatic_consent(string $type, string $title): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => false]);
        $report = $this->report($link, ['report_type' => $type, 'send_to_bot' => false, 'enabled' => false]);
        $report->schedule->delete();
        $context = new BotContext($link->telegraph_chat_id, $link->telegram_id, 'en', 'manual-report', $link->id, $link->user_id);
        $screen = app(BotRouter::class)->dispatch('files', $context, ['k' => 'site_intel_report']);
        $jsonButton = collect($screen->buttons)->first(fn ($button) => ($button->parameters['f'] ?? null) === 'json');
        $this->assertSame($report->id, $jsonButton->parameters['i']);

        app(BotRouter::class)->dispatch('send', $context, $jsonButton->parameters);
        $delivery = BotDelivery::query()->sole();
        $this->assertFalse($delivery->automatic);
        $this->mock(BotTransport::class)->shouldReceive('document')->once()->with($link->telegraph_chat_id,
            \Mockery::on(fn (BotDocument $document): bool => json_decode(file_get_contents($document->path), true, flags: JSON_THROW_ON_ERROR) === $report->data));

        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }

    public function test_manually_generated_report_from_paused_schedule_is_delivered_when_opted_in(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link, ['enabled' => false]);
        $report->update(['is_manual' => true]);

        event(new SiteIntelReportCompleted($report->id));
        $delivery = BotDelivery::query()->sole();
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        Telegraph::assertSentData('sendDocument', ['chat_id' => $link->telegram_id], false);
    }

    public function test_history_labels_identify_report_type_and_use_schedule_timezone(): void
    {
        app()->setLocale('en');
        $link = $this->linkedUser();
        $report = $this->report($link);
        $report->update(['completed_at' => '2026-10-08 21:30:00']);

        $listing = app(SiteIntelReportArtifactProvider::class)->listing($link->user_id, 1);

        $this->assertSame('https://example.com/ / Website analytics / 09.10.2026 00:30', $listing->items()[0]['label']);
    }

    public function test_foreign_unfinished_and_unsupported_reports_are_unavailable(): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        $other = $this->linkedUser('10002');
        $provider = app(SiteIntelReportArtifactProvider::class);
        $this->assertCount(0, $provider->listing($other->user_id, 1)->items());
        foreach ([[$other->user_id, 'json'], [$link->user_id, '../env']] as [$userId, $format]) {
            try {
                $provider->document($userId, $report->id, $format, 'en');
                $this->fail('Unavailable report must not be generated.');
            } catch (ArtifactUnavailable) {
                $this->addToAssertionCount(1);
            }
        }
        $report->update(['status' => SiteIntelScheduledReport::PENDING]);
        app(SiteIntelReportBotListener::class)->handle(new SiteIntelReportCompleted($report->id));
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        $this->assertCount(0, $provider->listing($link->user_id, 1)->items());
        $this->expectException(ArtifactUnavailable::class);
        $provider->document($link->user_id, $report->id, 'json', 'en');
    }

    public function test_forged_manual_reference_cannot_deliver_another_users_report(): void
    {
        $owner = $this->linkedUser();
        $report = $this->report($owner);
        $other = $this->linkedUser('10002');
        app(DeliveryOutbox::class)->enqueue($other, 'site_intel_report', (string) $report->id, ['format' => 'json'], false, 'forged-reference');
        $delivery = BotDelivery::query()->sole();

        app()->call([new DeliverBotMessage($delivery->id, $other->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SKIPPED, $delivery->fresh()->status);
        Telegraph::assertNothingSent();
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }

    #[DataProvider('reportTypes')]
    public function test_forged_manual_reference_cannot_deliver_a_disallowed_type_while_another_type_is_allowed(string $type, string $title): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link, ['report_type' => $type]);
        $this->revoke('feature', $link, $report);
        app(DeliveryOutbox::class)->enqueue($link, 'site_intel_report', (string) $report->id, ['format' => 'json'], false, 'forged-type-reference');
        $delivery = BotDelivery::query()->sole();

        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SKIPPED, $delivery->fresh()->status);
        Telegraph::assertNothingSent();
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }

    #[DataProvider('revokedAccess')]
    public function test_delivery_rechecks_current_permissions_before_sending(string $reason, string $type): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link, ['report_type' => $type]);
        app(SiteIntelReportBotListener::class)->handle(new SiteIntelReportCompleted($report->id));
        $delivery = BotDelivery::query()->sole();
        $this->revoke($reason, $link, $report);

        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SKIPPED, $delivery->fresh()->status);
        Telegraph::assertNothingSent();
    }

    #[DataProvider('revokedAccess')]
    public function test_permissions_changed_during_rendering_prevent_upload_and_temporary_file_is_removed(string $reason, string $type): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link, ['report_type' => $type]);
        app(SiteIntelReportBotListener::class)->handle(new SiteIntelReportCompleted($report->id));
        $delivery = BotDelivery::query()->sole();
        $provider = $this->mock(ArtifactProvider::class);
        $provider->shouldReceive('key')->andReturn('site_intel_report');
        $provider->shouldReceive('document')->once()->andReturnUsing(function () use ($reason, $link, $report): BotDocument {
            $document = app(TemporaryDocuments::class)->create('report.html', fn (string $path) => Storage::disk('local')->put($path, '<html></html>'));
            $this->revoke($reason, $link, $report);

            return $document;
        });
        $this->instance(ArtifactRegistry::class, new ArtifactRegistry([$provider]));

        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SKIPPED, $delivery->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
        Telegraph::assertNothingSent();
    }

    public static function revokedAccess(): array
    {
        $cases = [];
        foreach (['analytics', 'seo-audit'] as $type) {
            foreach (['exports', 'schedule', 'paused', 'deleted', 'blocked', 'feature'] as $reason) {
                $cases[$type.' '.$reason] = [$reason, $type];
            }
        }

        return $cases;
    }

    public function test_history_exposes_only_permitted_report_types_without_spending_quota(): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        $seoReport = $this->report($link, ['report_type' => 'seo-audit']);
        $provider = app(SiteIntelReportArtifactProvider::class);
        $this->assertCount(2, $provider->listing($link->user_id, 1)->items());
        $this->assertDatabaseCount('feature_usage_daily', 0);
        config()->set('access.plans.free', ['site-intel.analytics' => 0, 'site-intel.seo-audit' => 100]);

        $items = $provider->listing($link->user_id, 1)->items();
        $this->assertCount(1, $items);
        $this->assertSame($seoReport->id, $items[0]['id']);
        // Generic access to the other report type must not reveal this saved snapshot.
        $this->expectException(ArtifactUnavailable::class);
        $provider->document($link->user_id, $report->id, 'json', 'en');
    }

    public function test_queue_failure_keeps_the_completed_report_and_pending_delivery_available(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        Bus::shouldReceive('dispatch')->andThrow(new \RuntimeException('Queue unavailable'));

        app(SiteIntelReportBotListener::class)->handle(new SiteIntelReportCompleted($report->id));

        $this->assertSame(SiteIntelScheduledReport::COMPLETED, $report->fresh()->status);
        $delivery = BotDelivery::query()->sole();
        $this->assertSame(BotDelivery::PENDING, $delivery->status);
        $this->assertNull($delivery->dispatched_at);
    }

    public function test_enqueue_failure_is_rethrown_so_completion_delivery_can_be_replayed(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        $this->mock(FeatureAccessServiceInterface::class)->shouldReceive('inspect')->once()->andThrow(new \RuntimeException('Access service unavailable'));

        try {
            app(SiteIntelReportBotListener::class)->handle(new SiteIntelReportCompleted($report->id));
            $this->fail('Failed enqueue must remain retryable.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Access service unavailable', $exception->getMessage());
        }

        $this->assertSame(SiteIntelScheduledReport::COMPLETED, $report->fresh()->status);
        $this->assertNull($report->fresh()->completion_notified_at);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
    }

    private function revoke(string $reason, BotLink $link, SiteIntelScheduledReport $report): void
    {
        match ($reason) {
            'exports' => $link->update(['exports_enabled' => false]),
            'schedule' => $report->schedule->update(['send_to_bot' => false]),
            'paused' => $report->schedule->update(['enabled' => false]),
            'deleted' => $report->schedule->delete(),
            'blocked' => $link->user->update(['is_blocked' => true]),
            'feature' => config()->set('access.plans.free', [
                'site-intel.'.$report->report_type => 0,
                'site-intel.'.($report->report_type === 'analytics' ? 'seo-audit' : 'analytics') => 100,
            ]),
        };
    }

    private function report(BotLink $link, array $preferences = []): SiteIntelScheduledReport
    {
        $schedule = SiteIntelReportSchedule::query()->create([
            'user_id' => $link->user_id, 'name' => 'Website checks', 'targets' => ['https://example.com/'],
            'report_type' => 'analytics', 'crawl_limit' => 8, 'platform_type' => 'auto',
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Moscow',
            'send_to_bot' => true, 'enabled' => true, 'next_run_at' => now()->addDay(), ...$preferences,
        ]);

        return $schedule->reports()->create([
            'user_id' => $link->user_id, 'target_url' => 'https://example.com/', 'scheduled_for' => now(),
            'report_type' => $schedule->report_type, 'crawl_limit' => $schedule->crawl_limit, 'platform_type' => $schedule->platform_type,
            'date_from' => now(), 'date_to' => now(), 'available_at' => now(),
            'status' => SiteIntelScheduledReport::COMPLETED, 'completed_at' => now(),
            'data' => ['target' => ['url' => 'https://example.com/<script>private</script>', 'domain' => 'example.com',
                'finalUrl' => 'https://example.com/<script>private</script>', 'host' => 'example.com'],
                'checkedAt' => '2026-10-09T09:00:00+03:00',
                'reportSchedule' => ['type' => $schedule->report_type, 'targetUrl' => 'https://example.com/',
                    'scheduledFor' => '2026-10-09T09:00:00+03:00', 'checkedAt' => '2026-10-09T09:00:00+03:00',
                    'timezone' => 'Europe/Moscow', 'frequency' => '1'],
                'overview' => ['score' => ['value' => 70, 'level' => 'medium']],
                'score' => ['value' => 70, 'level' => 'medium']],
        ]);
    }
}

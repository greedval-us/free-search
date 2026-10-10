<?php

namespace Tests\Feature\TelegramBot;

use App\Integrations\TelegramBot\YouTubeAnalyticsReportArtifactProvider;
use App\Integrations\TelegramBot\YouTubeAnalyticsReportBotListener;
use App\Models\YouTubeAnalyticsReport;
use App\Models\YouTubeAnalyticsSchedule;
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
use App\Modules\YouTube\Analytics\Reports\Events\AnalyticsReportCompleted;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

final class YouTubeAnalyticsReportIntegrationTest extends TelegramBotTestCase
{
    public function test_completed_report_is_automatically_sent_once_as_html_and_temporary_file_is_removed(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        DB::transaction(fn () => event(new AnalyticsReportCompleted($report->id)));
        event(new AnalyticsReportCompleted($report->id));

        $this->assertDatabaseCount('telegram_bot_deliveries', 1);
        $delivery = BotDelivery::query()->sole();
        $this->assertSame('youtube_analytics_report', $delivery->kind);
        $this->assertSame(['format' => 'html'], $delivery->payload);
        Queue::assertPushed(DeliverBotMessage::class, 1);
        $this->assertInstanceOf(YouTubeAnalyticsReportArtifactProvider::class, app(ArtifactRegistry::class)->get('youtube_analytics_report'));
        $this->mock(BotTransport::class)->shouldReceive('document')->once()->with($link->telegraph_chat_id,
            \Mockery::on(function (BotDocument $document) use ($report): bool {
                $content = file_get_contents($document->path);

                return $document->filename === 'youtube-analytics-'.$report->id.'.html'
                    && str_contains($content, 'publicgroup') && str_contains($content, '&lt;script&gt;private&lt;/script&gt;')
                    && ! str_contains($content, '<script>private</script>');
            }));

        $job = new DeliverBotMessage($delivery->id, $link->telegram_id);
        app()->call([$job, 'handle']);
        app()->call([$job, 'handle']);

        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }

    #[DataProvider('automaticDeliveryPreferences')]
    public function test_automatic_report_requires_schedule_and_file_delivery_consent(bool $scheduleConsent, bool $fileConsent, bool $accepted): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => $fileConsent]);
        $report = $this->report($link, ['send_to_bot' => $scheduleConsent]);

        app(YouTubeAnalyticsReportBotListener::class)->handle(new AnalyticsReportCompleted($report->id));

        $this->assertDatabaseCount('telegram_bot_deliveries', $accepted ? 1 : 0);
        Telegraph::assertNothingSent();
    }

    public static function automaticDeliveryPreferences(): array
    {
        return ['schedule opted out' => [false, true, false], 'file delivery opted out' => [true, false, false], 'both opted in' => [true, true, true]];
    }

    public function test_manual_report_history_delivers_json_without_automatic_consent(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => false]);
        $report = $this->report($link, ['send_to_bot' => false, 'enabled' => false]);
        $context = new BotContext($link->telegraph_chat_id, $link->telegram_id, 'en', 'manual-report', $link->id, $link->user_id);
        $screen = app(BotRouter::class)->dispatch('files', $context, ['k' => 'youtube_analytics_report']);
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

        event(new AnalyticsReportCompleted($report->id));
        $delivery = BotDelivery::query()->sole();
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        Telegraph::assertSentData('sendDocument', ['chat_id' => $link->telegram_id], false);
    }

    public function test_history_labels_use_schedule_timezone_for_utc_day_boundaries(): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        $report->update(['date_from' => '2026-10-08 21:00:00', 'date_to' => '2026-10-09 20:59:59']);

        $listing = app(YouTubeAnalyticsReportArtifactProvider::class)->listing($link->user_id, 1);

        $this->assertSame('@publicgroup / 09.10–09.10.2026', $listing->items()[0]['label']);
    }

    public function test_foreign_unfinished_and_unsupported_reports_are_unavailable(): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        $other = $this->linkedUser('10002');
        $provider = app(YouTubeAnalyticsReportArtifactProvider::class);
        $this->assertCount(0, $provider->listing($other->user_id, 1)->items());
        foreach ([[$other->user_id, 'json'], [$link->user_id, '../env']] as [$userId, $format]) {
            try {
                $provider->document($userId, $report->id, $format, 'en');
                $this->fail('Unavailable report must not be generated.');
            } catch (ArtifactUnavailable) {
                $this->addToAssertionCount(1);
            }
        }
        $report->update(['status' => YouTubeAnalyticsReport::PENDING]);
        app(YouTubeAnalyticsReportBotListener::class)->handle(new AnalyticsReportCompleted($report->id));
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
        app(DeliveryOutbox::class)->enqueue($other, 'youtube_analytics_report', (string) $report->id, ['format' => 'json'], false, 'forged-reference');
        $delivery = BotDelivery::query()->sole();

        app()->call([new DeliverBotMessage($delivery->id, $other->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SKIPPED, $delivery->fresh()->status);
        Telegraph::assertNothingSent();
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }

    #[DataProvider('revokedAccess')]
    public function test_delivery_rechecks_current_permissions_before_sending(string $reason): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        app(YouTubeAnalyticsReportBotListener::class)->handle(new AnalyticsReportCompleted($report->id));
        $delivery = BotDelivery::query()->sole();
        $this->revoke($reason, $link, $report);

        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SKIPPED, $delivery->fresh()->status);
        Telegraph::assertNothingSent();
    }

    #[DataProvider('revokedAccess')]
    public function test_permissions_changed_during_rendering_prevent_upload_and_temporary_file_is_removed(string $reason): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        app(YouTubeAnalyticsReportBotListener::class)->handle(new AnalyticsReportCompleted($report->id));
        $delivery = BotDelivery::query()->sole();
        $provider = $this->mock(ArtifactProvider::class);
        $provider->shouldReceive('key')->andReturn('youtube_analytics_report');
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
        return [['exports'], ['schedule'], ['paused'], ['deleted'], ['blocked'], ['feature']];
    }

    public function test_current_feature_access_is_required_for_history_without_spending_quota(): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        $provider = app(YouTubeAnalyticsReportArtifactProvider::class);
        $this->assertCount(1, $provider->listing($link->user_id, 1)->items());
        $this->assertDatabaseCount('feature_usage_daily', 0);
        config()->set('access.plans.free', ['youtube.analytics' => 0]);

        $this->assertCount(0, $provider->listing($link->user_id, 1)->items());
        $this->assertFalse(app(DeliveryOutbox::class)->enqueue($link, 'youtube_analytics_report', (string) $report->id, ['format' => 'json'], false));
        $this->expectException(ArtifactUnavailable::class);
        $provider->document($link->user_id, $report->id, 'json', 'en');
    }

    public function test_queue_failure_keeps_the_completed_report_and_pending_delivery_available(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        Bus::shouldReceive('dispatch')->andThrow(new \RuntimeException('Queue unavailable'));

        app(YouTubeAnalyticsReportBotListener::class)->handle(new AnalyticsReportCompleted($report->id));

        $this->assertSame(YouTubeAnalyticsReport::COMPLETED, $report->fresh()->status);
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
            app(YouTubeAnalyticsReportBotListener::class)->handle(new AnalyticsReportCompleted($report->id));
            $this->fail('Failed enqueue must remain retryable.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Access service unavailable', $exception->getMessage());
        }

        $this->assertSame(YouTubeAnalyticsReport::COMPLETED, $report->fresh()->status);
        $this->assertNull($report->fresh()->completion_notified_at);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
    }

    private function revoke(string $reason, BotLink $link, YouTubeAnalyticsReport $report): void
    {
        match ($reason) {
            'exports' => $link->update(['exports_enabled' => false]),
            'schedule' => $report->schedule->update(['send_to_bot' => false]),
            'paused' => $report->schedule->update(['enabled' => false]),
            'deleted' => $report->schedule->delete(),
            'blocked' => $link->user->update(['is_blocked' => true]),
            'feature' => config()->set('access.plans.free', ['youtube.analytics' => 0]),
        };
    }

    private function report(BotLink $link, array $preferences = []): YouTubeAnalyticsReport
    {
        $schedule = YouTubeAnalyticsSchedule::query()->create([
            'user_id' => $link->user_id, 'name' => 'Public channel analytics', 'channels' => ['@publicgroup'],
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Moscow',
            'send_to_bot' => true, 'enabled' => true, 'next_run_at' => now()->addDay(), ...$preferences,
        ]);

        return $schedule->reports()->create([
            'user_id' => $link->user_id, 'channel_input' => '@publicgroup', 'scheduled_for' => now(),
            'date_from' => now()->subDay(), 'date_to' => now(), 'available_at' => now(),
            'status' => YouTubeAnalyticsReport::COMPLETED, 'completed_at' => now(),
            'data' => ['mode' => 'channel', 'channelId' => 'UCabcdefghijklmnopqrstuv',
                'channel' => ['title' => 'publicgroup <script>private</script>'],
                'range' => ['channelInput' => '@publicgroup', 'dateFrom' => '2026-10-08T00:00:00+03:00',
                    'dateTo' => '2026-10-08T23:59:59+03:00', 'timezone' => 'Europe/Moscow', 'periodDays' => 1],
                'totals' => ['videos' => 4, 'views' => 123],
                'methodology' => ['metricsBasis' => 'lifetime_statistics_of_public_videos_published_in_range']],
        ]);
    }
}

<?php

namespace Tests\Feature\TelegramBot;

use App\Integrations\TelegramBot\NewsMediaReportArtifactProvider;
use App\Integrations\TelegramBot\NewsMediaReportBotListener;
use App\Models\NewsMediaReportSchedule;
use App\Models\NewsMediaScheduledReport;
use App\Modules\NewsMediaIntel\Application\Reports\Events\NewsMediaReportCompleted;
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
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

final class NewsMediaReportIntegrationTest extends TelegramBotTestCase
{
    public function test_completed_report_is_sent_once_as_existing_html_and_temporary_file_is_removed(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        $this->assertInstanceOf(NewsMediaReportArtifactProvider::class, app(ArtifactRegistry::class)->get('news_media_report'));
        $this->mock(BotTransport::class)->shouldReceive('document')->once()->with($link->telegraph_chat_id,
            \Mockery::on(function (BotDocument $document) use ($report): bool {
                $content = file_get_contents($document->path);

                return $document->filename === 'news-media-report-'.$report->id.'.html'
                    && str_contains($content, 'News and media: SEO and marketing analytics')
                    && str_contains($content, 'example.com') && str_contains($content, '&lt;script&gt;private&lt;/script&gt;')
                    && ! str_contains($content, '<script>private</script>');
            }));

        DB::transaction(fn () => event(new NewsMediaReportCompleted($report->id)));
        event(new NewsMediaReportCompleted($report->id));
        $delivery = BotDelivery::query()->sole();
        $job = new DeliverBotMessage($delivery->id, $link->telegram_id);
        app()->call([$job, 'handle']);
        app()->call([$job, 'handle']);

        $this->assertDatabaseCount('telegram_bot_deliveries', 1);
        $this->assertSame('news_media_report', $delivery->kind);
        $this->assertSame(['format' => 'html'], $delivery->payload);
        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        Queue::assertPushed(DeliverBotMessage::class, 1);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    #[DataProvider('deliveryConsent')]
    public function test_automatic_report_requires_both_schedule_and_file_delivery_consent(bool $scheduleConsent, bool $fileConsent, bool $accepted): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => $fileConsent]);
        $report = $this->report($link, ['send_to_bot' => $scheduleConsent]);

        app(NewsMediaReportBotListener::class)->handle(new NewsMediaReportCompleted($report->id));

        $this->assertDatabaseCount('telegram_bot_deliveries', $accepted ? 1 : 0);
        Telegraph::assertNothingSent();
    }

    public static function deliveryConsent(): array
    {
        return ['schedule opted out' => [false, true, false], 'file delivery opted out' => [true, false, false], 'both opted in' => [true, true, true]];
    }

    public function test_manually_generated_report_from_paused_schedule_is_delivered_when_opted_in(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link, ['enabled' => false]);
        $report->update(['is_manual' => true]);

        event(new NewsMediaReportCompleted($report->id));
        $delivery = BotDelivery::query()->sole();
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        Telegraph::assertSentData('sendDocument', ['chat_id' => $link->telegram_id], false);
    }

    public function test_manual_history_delivers_saved_json_after_schedule_deletion_without_automatic_consent(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => false]);
        $report = $this->report($link, ['send_to_bot' => false, 'enabled' => false]);
        $report->schedule->delete();
        $context = new BotContext($link->telegraph_chat_id, $link->telegram_id, 'en', 'manual-report', $link->id, $link->user_id);
        $screen = app(BotRouter::class)->dispatch('files', $context, ['k' => 'news_media_report']);
        $jsonButton = collect($screen->buttons)->first(fn ($button) => ($button->parameters['f'] ?? null) === 'json');
        $this->mock(BotTransport::class)->shouldReceive('document')->once()->with($link->telegraph_chat_id,
            \Mockery::on(fn (BotDocument $document): bool => json_decode(file_get_contents($document->path), true, flags: JSON_THROW_ON_ERROR) === $report->data));

        app(BotRouter::class)->dispatch('send', $context, $jsonButton->parameters);
        $delivery = BotDelivery::query()->sole();
        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame($report->id, $jsonButton->parameters['i']);
        $this->assertFalse($delivery->automatic);
        $this->assertSame(BotDelivery::SENT, $delivery->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }

    public function test_history_labels_use_the_schedule_timezone_without_spending_quota(): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        $report->update(['completed_at' => '2026-10-08 21:30:00']);

        $listing = app(NewsMediaReportArtifactProvider::class)->listing($link->user_id, 1);

        $this->assertSame('Acme market / 09.10.2026 00:30', $listing->items()[0]['label']);
        $this->assertDatabaseCount('feature_usage_daily', 0);
    }

    #[DataProvider('unavailableReports')]
    public function test_foreign_unfinished_and_unsupported_reports_are_unavailable(string $reason): void
    {
        $link = $this->linkedUser();
        $report = $this->report($link);
        $userId = $link->user_id;
        $format = 'json';
        match ($reason) {
            'foreign' => $userId = $this->linkedUser('10002')->user_id,
            'pending' => $report->update(['status' => NewsMediaScheduledReport::PENDING]),
            'missing data' => $report->update(['data' => null]),
            'unsupported format' => $format = '../env',
            'blocked' => $link->user->update(['is_blocked' => true]),
            'unverified' => $link->user->forceFill(['email_verified_at' => null])->save(),
        };
        $provider = app(NewsMediaReportArtifactProvider::class);
        if ($reason !== 'unsupported format') {
            $this->assertCount(0, $provider->listing($userId, 1)->items());
        }

        $this->expectException(ArtifactUnavailable::class);
        $provider->document($userId, $report->id, $format, 'en');
    }

    public static function unavailableReports(): array
    {
        return array_combine($reasons = ['foreign', 'pending', 'missing data', 'unsupported format', 'blocked', 'unverified'], array_map(fn ($reason) => [$reason], $reasons));
    }

    public function test_forged_manual_reference_cannot_deliver_another_users_report(): void
    {
        $owner = $this->linkedUser();
        $report = $this->report($owner);
        $other = $this->linkedUser('10002');
        app(DeliveryOutbox::class)->enqueue($other, 'news_media_report', (string) $report->id, ['format' => 'json'], false, 'forged-reference');
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
        app(NewsMediaReportBotListener::class)->handle(new NewsMediaReportCompleted($report->id));
        $delivery = BotDelivery::query()->sole();
        $this->revoke($reason, $link, $report);

        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame($reason === 'unlinked' ? null : BotDelivery::SKIPPED, $delivery->fresh()?->status);
        Telegraph::assertNothingSent();
    }

    #[DataProvider('revokedAccess')]
    public function test_permissions_changed_during_rendering_prevent_upload_and_remove_temporary_file(string $reason): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        app(NewsMediaReportBotListener::class)->handle(new NewsMediaReportCompleted($report->id));
        $delivery = BotDelivery::query()->sole();
        $provider = $this->mock(ArtifactProvider::class);
        $provider->shouldReceive('key')->andReturn('news_media_report');
        $provider->shouldReceive('document')->once()->andReturnUsing(function () use ($reason, $link, $report): BotDocument {
            $document = app(TemporaryDocuments::class)->create('report.html', fn (string $path) => Storage::disk('local')->put($path, '<html></html>'));
            $this->revoke($reason, $link, $report);

            return $document;
        });
        $this->instance(ArtifactRegistry::class, new ArtifactRegistry([$provider]));

        app()->call([new DeliverBotMessage($delivery->id, $link->telegram_id), 'handle']);

        $this->assertSame($reason === 'unlinked' ? null : BotDelivery::SKIPPED, $delivery->fresh()?->status);
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
        Telegraph::assertNothingSent();
    }

    public static function revokedAccess(): array
    {
        return array_combine($reasons = ['exports', 'schedule', 'paused', 'deleted', 'blocked', 'unverified', 'unlinked', 'owner', 'status', 'data'], array_map(fn ($reason) => [$reason], $reasons));
    }

    public function test_queue_failure_keeps_completed_report_and_pending_delivery_available(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        Bus::shouldReceive('dispatch')->andThrow(new \RuntimeException('Queue unavailable'));

        app(NewsMediaReportBotListener::class)->handle(new NewsMediaReportCompleted($report->id));

        $this->assertSame(NewsMediaScheduledReport::COMPLETED, $report->fresh()->status);
        $this->assertSame($report->data, $report->fresh()->data);
        $delivery = BotDelivery::query()->sole();
        $this->assertSame(BotDelivery::PENDING, $delivery->status);
        $this->assertNull($delivery->dispatched_at);
    }

    public function test_enqueue_failure_is_rethrown_for_completion_delivery_replay(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->report($link);
        BotDelivery::creating(static fn () => throw new \RuntimeException('Outbox unavailable'));

        try {
            app(NewsMediaReportBotListener::class)->handle(new NewsMediaReportCompleted($report->id));
            $this->fail('Failed enqueue must remain retryable.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Outbox unavailable', $exception->getMessage());
        }

        $this->assertSame(NewsMediaScheduledReport::COMPLETED, $report->fresh()->status);
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
    }

    private function revoke(string $reason, BotLink $link, NewsMediaScheduledReport $report): void
    {
        match ($reason) {
            'exports' => $link->update(['exports_enabled' => false]),
            'schedule' => $report->schedule->update(['send_to_bot' => false]),
            'paused' => $report->schedule->update(['enabled' => false]),
            'deleted' => $report->schedule->delete(),
            'blocked' => $link->user->update(['is_blocked' => true]),
            'unverified' => $link->user->forceFill(['email_verified_at' => null])->save(),
            'unlinked' => $link->delete(),
            'owner' => $report->update(['user_id' => $this->linkedUser('10002')->user_id]),
            'status' => $report->update(['status' => NewsMediaScheduledReport::FAILED]),
            'data' => $report->update(['data' => null]),
        };
    }

    private function report(BotLink $link, array $preferences = []): NewsMediaScheduledReport
    {
        $schedule = NewsMediaReportSchedule::query()->create([
            'user_id' => $link->user_id, 'name' => 'Market checks', 'queries' => ['Acme market'],
            'brand' => 'Acme', 'competitors' => ['Other'], 'domain' => 'example.com',
            'search_options' => ['language' => 'en', 'timeRange' => 'day', 'safeSearch' => 0, 'engines' => [], 'maxPages' => 1],
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Moscow',
            'send_to_bot' => true, 'enabled' => true, 'next_run_at' => now()->addDay(), ...$preferences,
        ]);

        return $schedule->reports()->create([
            'user_id' => $link->user_id, 'query' => 'Acme market', 'scheduled_for' => now(),
            'brand' => $schedule->brand, 'competitors' => $schedule->competitors,
            'domain' => $schedule->domain, 'search_options' => $schedule->search_options, 'available_at' => now(),
            'status' => NewsMediaScheduledReport::COMPLETED, 'completed_at' => now(),
            'data' => ['query' => 'Acme <script>private</script>', 'checkedAt' => '2026-10-09T09:00:00+03:00',
                'options' => ['language' => 'en', 'timeRange' => 'day'],
                'summary' => ['mentions' => 1, 'publishers' => 1, 'knownDates' => 0, 'undatedMentions' => 1],
                'visibility' => ['domain' => 'example.com', 'domainMatches' => 1, 'bestObservedPosition' => 1, 'pages' => []],
            ],
        ]);
    }
}

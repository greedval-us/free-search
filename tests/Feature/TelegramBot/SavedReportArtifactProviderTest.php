<?php

namespace Tests\Feature\TelegramBot;

use App\Integrations\TelegramBot\AnalyticsReportArtifactProvider;
use App\Integrations\TelegramBot\SiteIntelReportArtifactProvider;
use App\Models\SiteIntelReportSchedule;
use App\Models\TelegramAnalyticsReport;
use App\Models\TelegramAnalyticsSchedule;
use App\Modules\Telegram\Analytics\Reports\Events\AnalyticsReportCompleted;
use App\Modules\TelegramBot\Application\ScheduledReportRegistry;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Models\BotLink;
use App\Support\Reports\SavedReportRenderer;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Queue;

final class SavedReportArtifactProviderTest extends TelegramBotTestCase
{
    public function test_completed_status_without_saved_data_cannot_enqueue_or_deliver_a_social_report(): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => true]);
        $report = $this->telegramReport($link);
        $report->update(['data' => null]);
        $provider = app(ScheduledReportRegistry::class)->find('analytics_report');

        event(new AnalyticsReportCompleted($report->id));

        $this->assertNull($provider->automaticRecipient($report->id));
        $this->assertFalse($provider->allowsDelivery($link->user, $report->id, true));
        $this->assertFalse($provider->allowsDelivery($link->user, $report->id, false));
        $this->assertDatabaseCount('telegram_bot_deliveries', 0);
        Queue::assertNothingPushed();
        Telegraph::assertNothingSent();
    }

    public function test_telegram_comparison_is_rendered_without_mutating_the_full_saved_json_snapshot(): void
    {
        $link = $this->linkedUser();
        $report = $this->telegramReport($link);
        $provider = app(AnalyticsReportArtifactProvider::class);
        app()->setLocale('en');

        $html = $provider->document($link->user_id, $report->id, 'html', 'ru');
        $json = $provider->document($link->user_id, $report->id, 'json', 'ru');

        $this->assertStringContainsString('к прошлому периоду: +100.0%', file_get_contents($html->path));
        $this->assertSame($report->data, json_decode(file_get_contents($json->path), true, flags: JSON_THROW_ON_ERROR));
        $this->assertSame(['summary' => ['totals' => ['messages' => 2]]], $report->fresh()->data['previousReport']);
        $this->assertSame('en', app()->getLocale());
        app(TemporaryDocuments::class)->remove($html);
        app(TemporaryDocuments::class)->remove($json);
    }

    public function test_site_report_uses_snapshot_timezone_when_the_schedule_timezone_changes(): void
    {
        $link = $this->linkedUser();
        $schedule = SiteIntelReportSchedule::query()->create([
            'user_id' => $link->user_id, 'name' => 'Website checks', 'targets' => ['https://example.com/'],
            'report_type' => 'analytics', 'crawl_limit' => 8, 'platform_type' => 'auto',
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'UTC',
            'send_to_bot' => false, 'enabled' => true, 'next_run_at' => now()->addDay(),
        ]);
        $report = $schedule->reports()->create([
            'user_id' => $link->user_id, 'target_url' => 'https://example.com/', 'scheduled_for' => now(),
            'report_type' => 'analytics', 'crawl_limit' => 8, 'platform_type' => 'auto',
            'date_from' => now(), 'date_to' => now(), 'status' => 'completed', 'completed_at' => '2026-10-08 21:30:00',
            'data' => ['target' => ['url' => 'https://example.com/', 'domain' => 'example.com'],
                'reportSchedule' => ['timezone' => 'Europe/Moscow'],
                'overview' => ['score' => ['value' => 70, 'level' => 'medium']]],
        ]);

        $document = app(SiteIntelReportArtifactProvider::class)->document($link->user_id, $report->id, 'html', 'en');

        $this->assertStringContainsString('Generated at 09.10.2026 00:30', file_get_contents($document->path));
        app(TemporaryDocuments::class)->remove($document);
    }

    public function test_saved_renderer_restores_the_callers_locale_when_rendering_fails(): void
    {
        app()->setLocale('en');

        try {
            app(SavedReportRenderer::class)->html('reports.missing-test-view', [], 'ru');
            $this->fail('A missing view must fail.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('reports.missing-test-view', $exception->getMessage());
        }

        $this->assertSame('en', app()->getLocale());
    }

    private function telegramReport(BotLink $link): TelegramAnalyticsReport
    {
        $schedule = TelegramAnalyticsSchedule::query()->create([
            'user_id' => $link->user_id, 'name' => 'Group analytics', 'groups' => ['publicgroup'],
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'Europe/Moscow',
            'send_to_bot' => true, 'enabled' => true, 'next_run_at' => now()->addDay(),
        ]);

        return $schedule->reports()->create([
            'user_id' => $link->user_id, 'chat_username' => 'publicgroup', 'scheduled_for' => now(),
            'date_from' => now()->subDay(), 'date_to' => now(), 'available_at' => now(),
            'status' => TelegramAnalyticsReport::COMPLETED, 'completed_at' => now(),
            'data' => ['range' => ['chatUsername' => 'publicgroup', 'label' => 'Last day'],
                'summary' => ['totals' => ['messages' => 4]],
                'previousReport' => ['summary' => ['totals' => ['messages' => 2]]]],
        ]);
    }
}

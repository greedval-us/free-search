<?php

namespace App\Integrations\TelegramBot;

use App\Models\SiteIntelScheduledReport;
use App\Models\User;
use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Services\Access\SiteIntelReportAccess;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Localizable;

final readonly class SiteIntelReportArtifactProvider implements ArtifactProvider
{
    use Localizable;

    private const FORMATS = ['html', 'json'];

    public function __construct(private BotConfig $config, private TemporaryDocuments $files, private SiteIntelReportAccess $access) {}

    public function key(): string
    {
        return 'site_intel_report';
    }

    public function listing(int $userId, int $page): Paginator
    {
        return $this->query($userId)->latest('id')
            ->simplePaginate($this->config->integer('page_size'), ['id', 'schedule_id', 'target_url', 'report_type', 'completed_at'], 'page', $page)
            ->through(fn (SiteIntelScheduledReport $report): array => [
                'id' => $report->id,
                'label' => $report->target_url.' / '.__('site_intel_reports.report.'.$report->report_type).' / '
                    .$report->completed_at?->setTimezone($report->schedule?->timezone ?? config('app.timezone'))->format('d.m.Y H:i'),
                'formats' => self::FORMATS,
            ]);
    }

    public function document(int $userId, int $id, string $format, string $locale): BotDocument
    {
        $report = $this->query($userId)->find($id);
        if ($report === null || ! in_array($format, self::FORMATS, true)) {
            throw new ArtifactUnavailable;
        }

        return $this->withLocale($locale, fn () => $this->files->create('site-intel-report-'.$id.'.'.$format, function (string $path) use ($report, $format, $locale): void {
            $data = $report->data;
            if ($format === 'json') {
                $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } else {
                $content = view($report->report_type === 'seo-audit' ? 'reports.site-intel.seo-audit' : 'reports.site-intel.analytics', [
                    'report' => $data, 'locale' => $locale,
                    'generatedAt' => $report->completed_at?->setTimezone($data['reportSchedule']['timezone'] ?? $report->schedule?->timezone ?? config('app.timezone'))->format('d.m.Y H:i'),
                ])->render();
            }
            Storage::disk('local')->put($path, $content);
        }));
    }

    private function query(int $userId): Builder
    {
        $query = SiteIntelScheduledReport::query()->with('schedule:id,timezone')->where('user_id', $userId)
            ->where('status', SiteIntelScheduledReport::COMPLETED)->whereNotNull('data');
        $user = User::query()->find($userId);
        if ($user === null || $user->isBlocked() || ! $user->hasVerifiedEmail()) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('report_type', $this->access->availableTypes($user));
        }

        return $query;
    }
}

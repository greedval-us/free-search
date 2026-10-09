<?php

namespace App\Integrations\TelegramBot;

use App\Models\User;
use App\Models\YouTubeAnalyticsReport;
use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Localizable;

final readonly class YouTubeAnalyticsReportArtifactProvider implements ArtifactProvider
{
    use Localizable;

    private const FORMATS = ['html', 'json'];

    public function __construct(private BotConfig $config, private TemporaryDocuments $files, private FeatureAccessServiceInterface $access) {}

    public function key(): string
    {
        return 'youtube_analytics_report';
    }

    public function listing(int $userId, int $page): Paginator
    {
        return $this->query($userId)->latest('id')
            ->simplePaginate($this->config->integer('page_size'), ['id', 'schedule_id', 'channel_input', 'date_from', 'date_to'], 'page', $page)
            ->through(fn (YouTubeAnalyticsReport $report): array => [
                'id' => $report->id,
                'label' => $report->channel_input.' / '.$report->date_from?->setTimezone($report->schedule?->timezone ?? config('app.timezone'))->format('d.m')
                    .'–'.$report->date_to?->setTimezone($report->schedule?->timezone ?? config('app.timezone'))->format('d.m.Y'),
                'formats' => self::FORMATS,
            ]);
    }

    public function document(int $userId, int $id, string $format, string $locale): BotDocument
    {
        $report = $this->query($userId)->find($id);
        if ($report === null || ! in_array($format, self::FORMATS, true)) {
            throw new ArtifactUnavailable;
        }

        return $this->withLocale($locale, fn () => $this->files->create('youtube-analytics-'.$id.'.'.$format, function (string $path) use ($report, $format, $locale): void {
            $data = $report->data;
            if ($format === 'json') {
                $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } else {
                $content = view('reports.youtube.analytics', [
                    'report' => $data, 'locale' => $locale,
                    'generatedAt' => $report->completed_at?->setTimezone($report->schedule?->timezone ?? config('app.timezone'))->format('d.m.Y H:i'),
                ])->render();
            }
            Storage::disk('local')->put($path, $content);
        }));
    }

    private function query(int $userId): Builder
    {
        $query = YouTubeAnalyticsReport::query()->with('schedule:id,timezone')->where('user_id', $userId)
            ->where('status', YouTubeAnalyticsReport::COMPLETED)->whereNotNull('data');
        $user = User::query()->find($userId);
        if ($user === null || $user->isBlocked() || ! $user->hasVerifiedEmail()
            || ! $this->access->inspect($user, 'youtube.analytics', false)->allowed) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}

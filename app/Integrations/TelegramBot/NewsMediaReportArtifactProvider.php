<?php

namespace App\Integrations\TelegramBot;

use App\Models\NewsMediaScheduledReport;
use App\Models\User;
use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Support\BotConfig;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Localizable;

final readonly class NewsMediaReportArtifactProvider implements ArtifactProvider
{
    use Localizable;

    private const FORMATS = ['html', 'json'];

    public function __construct(private BotConfig $config, private TemporaryDocuments $files) {}

    public function key(): string
    {
        return 'news_media_report';
    }

    public function listing(int $userId, int $page): Paginator
    {
        return $this->query($userId)->latest('id')
            ->simplePaginate($this->config->integer('page_size'), ['id', 'schedule_id', 'query', 'completed_at'], 'page', $page)
            ->through(fn (NewsMediaScheduledReport $report): array => [
                'id' => $report->id,
                'label' => $report->query.' / '
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

        return $this->withLocale($locale, fn () => $this->files->create('news-media-report-'.$id.'.'.$format, function (string $path) use ($report, $format, $locale): void {
            $data = $report->data;
            $content = $format === 'json'
                ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                : view('reports.news-media-intel.analytics', ['report' => $data, 'locale' => $locale])->render();
            Storage::disk('local')->put($path, $content);
        }));
    }

    private function query(int $userId): Builder
    {
        $query = NewsMediaScheduledReport::query()->with('schedule:id,timezone')->where('user_id', $userId)
            ->where('status', NewsMediaScheduledReport::COMPLETED)->whereNotNull('data');
        $user = User::query()->find($userId);
        if ($user === null || $user->isBlocked() || ! $user->hasVerifiedEmail()) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}

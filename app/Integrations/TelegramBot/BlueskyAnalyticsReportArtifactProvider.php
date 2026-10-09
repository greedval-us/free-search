<?php

namespace App\Integrations\TelegramBot;

use App\Models\BlueskyAnalyticsReport;
use App\Models\User;
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

final readonly class BlueskyAnalyticsReportArtifactProvider implements ArtifactProvider
{
    use Localizable;

    private const FORMATS = ['html', 'json'];

    public function __construct(private BotConfig $config, private TemporaryDocuments $files, private FeatureAccessServiceInterface $access) {}

    public function key(): string
    {
        return 'bluesky_analytics_report';
    }

    public function listing(int $userId, int $page): Paginator
    {
        return $this->query($userId)->latest('id')
            ->simplePaginate($this->config->integer('page_size'), ['id', 'schedule_id', 'account_input', 'date_from', 'date_to'], 'page', $page)
            ->through(fn (BlueskyAnalyticsReport $report): array => [
                'id' => $report->id,
                'label' => $report->account_input.' / '.$report->date_from?->setTimezone($report->schedule?->timezone ?? config('app.timezone'))->format('d.m')
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

        return $this->withLocale($locale, fn () => $this->files->create('bluesky-analytics-'.$id.'.'.$format, function (string $path) use ($report, $format, $locale): void {
            $data = $report->data;
            if ($format === 'json') {
                $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } else {
                $content = view('reports.bluesky.analytics', [
                    'report' => $data, 'locale' => $locale,
                    'generatedAt' => $report->completed_at?->setTimezone($report->schedule?->timezone ?? config('app.timezone'))->format('d.m.Y H:i'),
                ])->render();
            }
            Storage::disk('local')->put($path, $content);
        }));
    }

    private function query(int $userId): Builder
    {
        $query = BlueskyAnalyticsReport::query()->with('schedule:id,timezone')->where('user_id', $userId)
            ->where('status', BlueskyAnalyticsReport::COMPLETED)->whereNotNull('data');
        $user = User::query()->find($userId);
        if ($user === null || $user->isBlocked() || ! $user->hasVerifiedEmail()
            || ! $this->access->inspect($user, 'bluesky.analytics', false)->allowed) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}

<?php

namespace App\Integrations\TelegramBot;

use App\Models\TelegramAnalyticsReport;
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

final readonly class AnalyticsReportArtifactProvider implements ArtifactProvider
{
    use Localizable;

    private const FORMATS = ['html', 'json'];

    public function __construct(private BotConfig $config, private TemporaryDocuments $files, private FeatureAccessServiceInterface $access) {}

    public function key(): string
    {
        return 'analytics_report';
    }

    public function listing(int $userId, int $page): Paginator
    {
        return $this->query($userId)->latest('id')
            ->simplePaginate($this->config->integer('page_size'), ['id', 'schedule_id', 'chat_username', 'date_from', 'date_to'], 'page', $page)
            ->through(fn (TelegramAnalyticsReport $report): array => [
                'id' => $report->id,
                'label' => '@'.$report->chat_username.' / '.$report->date_from?->setTimezone($report->schedule?->timezone ?? config('app.timezone'))->format('d.m')
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

        return $this->withLocale($locale, fn () => $this->files->create('telegram-analytics-'.$id.'.'.$format, function (string $path) use ($report, $format, $locale): void {
            $data = $report->data;
            if ($format === 'json') {
                $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } else {
                $previousReport = $data['previousReport'] ?? null;
                unset($data['previousReport']);
                $content = view('reports.telegram.analytics', [
                    'report' => $data, 'previousReport' => $previousReport, 'locale' => $locale,
                    'generatedAt' => $report->completed_at?->setTimezone($report->schedule?->timezone ?? config('app.timezone'))->format('d.m.Y H:i'),
                ])->render();
            }
            Storage::disk('local')->put($path, $content);
        }));
    }

    private function query(int $userId): Builder
    {
        $query = TelegramAnalyticsReport::query()->with('schedule:id,timezone')->where('user_id', $userId)
            ->where('status', TelegramAnalyticsReport::COMPLETED)->whereNotNull('data');
        $user = User::query()->find($userId);
        if ($user === null || $user->isBlocked() || ! $user->hasVerifiedEmail()
            || ! $this->access->inspect($user, 'telegram.analytics', false)->allowed) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}

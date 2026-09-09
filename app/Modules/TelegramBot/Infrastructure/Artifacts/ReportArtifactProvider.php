<?php

namespace App\Modules\TelegramBot\Infrastructure\Artifacts;

use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Models\BotReport;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Support\Reports\ReportsConfig;
use App\Support\Reports\ReportSnapshotStore;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Localizable;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

final readonly class ReportArtifactProvider implements ArtifactProvider
{
    use Localizable;

    public function __construct(private BotConfig $config, private ReportSnapshotStore $snapshots,
        private ReportsConfig $reports, private TemporaryDocuments $files) {}

    public function key(): string
    {
        return 'report';
    }

    public function listing(int $userId, int $page): Paginator
    {
        return $this->query($userId)->latest('updated_at')->simplePaginate($this->config->integer('page_size'), ['*'], 'page', $page)
            ->through(fn (BotReport $report): array => ['id' => $report->id, 'label' => $report->feature.' / '.$report->updated_at->format('d.m H:i'), 'formats' => ['html']]);
    }

    public function document(int $userId, int $id, string $format, string $locale): BotDocument
    {
        $record = $this->query($userId)->find($id);
        if ($record === null || $format !== 'html') {
            throw new ArtifactUnavailable;
        }
        try {
            $snapshot = $this->snapshots->get($userId, $record->feature, $record->parameters);
        } catch (GoneHttpException) {
            throw new ArtifactUnavailable;
        }

        return $this->withLocale($locale, function () use ($record, $snapshot, $locale): BotDocument {
            // Feature keys contain dots; access the configured allowlist directly.
            $definition = $this->config->get('reports')[$record->feature];
            $viewData = ($definition['wrapped'] ?? false) ? $snapshot : ['report' => $snapshot];
            $html = view($definition['view'], [...$viewData, 'locale' => $locale,
                'generatedAt' => now($this->reports->timezone())->format($this->reports->generatedAtFormat()),
            ])->render();

            return $this->files->create(str_replace('.', '-', $record->feature).'-'.$record->id.'.html',
                static function (string $path) use ($html): void {
                    Storage::disk('local')->put($path, $html);
                });
        });
    }

    private function query(int $userId): Builder
    {
        return BotReport::query()->where('user_id', $userId)->where('expires_at', '>', now())
            ->whereIn('feature', array_keys($this->config->get('reports', [])));
    }
}

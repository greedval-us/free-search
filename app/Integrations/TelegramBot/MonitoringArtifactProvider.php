<?php

namespace App\Integrations\TelegramBot;

use App\Models\MonitoringReport;
use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Services\Monitoring\MonitoringExports;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

final readonly class MonitoringArtifactProvider implements ArtifactProvider
{
    public function __construct(private MonitoringExports $exports, private BotConfig $config, private TemporaryDocuments $files) {}

    public function key(): string
    {
        return 'monitoring_document';
    }

    public function listing(int $userId, int $page): Paginator
    {
        return $this->available($userId)->where('file_status', 'ready')->latest('id')->simplePaginate($this->config->integer('page_size'), ['*'], 'page', $page)
            ->through(fn (MonitoringReport $report) => ['id' => $report->id,
                'label' => ($report->summary['title'] ?? $report->configuration['name'] ?? 'Monitoring').' / '.$report->end_at->format('Y-m-d'),
                'formats' => ['xlsx', 'json']]);
    }

    public function document(int $userId, int $id, string $format, string $locale): BotDocument
    {
        $report = $this->available($userId)->find($id);
        if ($report === null || ! in_array($format, ['xlsx', 'json'], true)) {
            throw new ArtifactUnavailable;
        }
        if ($report->file_status !== 'ready') {
            throw new ArtifactUnavailable('file_preparing');
        }
        $artifact = $this->exports->artifact($report, $format);

        return $this->files->create($artifact['name'], function (string $path) use ($artifact): void {
            $disk = Storage::disk('local');
            $disk->makeDirectory(dirname($path));
            if (! copy($artifact['path'], $disk->path($path))) {
                throw new ArtifactUnavailable;
            }
        });
    }

    private function available(int $userId): Builder
    {
        return MonitoringReport::query()->where('user_id', $userId)->whereIn('status', ['completed', 'partial', 'empty'])
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}

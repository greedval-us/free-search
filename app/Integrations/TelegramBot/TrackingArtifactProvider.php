<?php

namespace App\Integrations\TelegramBot;

use App\Modules\Telegram\Tracking\TrackingReports;
use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Support\BotConfig;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Localizable;
use Maatwebsite\Excel\Excel as Writer;
use Maatwebsite\Excel\Facades\Excel;

final readonly class TrackingArtifactProvider implements ArtifactProvider
{
    use Localizable;

    public function __construct(private TrackingReports $reports, private BotConfig $config, private TemporaryDocuments $files) {}

    public function key(): string
    {
        return 'tracking';
    }

    public function listing(int $userId, int $page): Paginator
    {
        return $this->reports->available($userId)->whereHas('messages')->latest('id')
            ->simplePaginate($this->config->integer('page_size'), ['*'], 'page', $page)
            ->through(fn ($task) => ['id' => $task->id, 'label' => $task->name, 'formats' => TrackingReports::FORMATS]);
    }

    public function document(int $userId, int $id, string $format, string $locale): BotDocument
    {
        $task = $this->reports->available($userId)->find($id);
        if ($task === null || ! in_array($format, TrackingReports::FORMATS, true)) {
            throw new ArtifactUnavailable;
        }

        return $this->withLocale($locale, fn () => $this->files->create('telegram-tracking-'.$id.'.'.$format, function (string $path) use ($task, $format): void {
            if ($format === 'xlsx') {
                Excel::store($this->reports->workbook($task), $path, 'local', Writer::XLSX);

                return;
            }
            Storage::disk('local')->makeDirectory(dirname($path));
            $stream = fopen(Storage::disk('local')->path($path), 'wb');
            if ($stream === false) {
                throw new ArtifactUnavailable;
            }
            try {
                foreach ($this->reports->json($task) as $chunk) {
                    if (fwrite($stream, $chunk) !== strlen($chunk)) {
                        throw new ArtifactUnavailable;
                    }
                }
            } finally {
                fclose($stream);
            }
        }));
    }
}

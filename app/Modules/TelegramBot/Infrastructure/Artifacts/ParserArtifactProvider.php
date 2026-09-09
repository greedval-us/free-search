<?php

namespace App\Modules\TelegramBot\Infrastructure\Artifacts;

use App\Models\ParserRun;
use App\Modules\Export\Excel\Contracts\ParserExportBuilderInterface;
use App\Modules\Export\Excel\WorkbookExport;
use App\Modules\ParserSupport\Contracts\ParserRunApplicationServiceInterface;
use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Support\BotConfig;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Localizable;
use Maatwebsite\Excel\Excel as Writer;
use Maatwebsite\Excel\Facades\Excel;

final readonly class ParserArtifactProvider implements ArtifactProvider
{
    use Localizable;

    private const FORMATS = ['xlsx', 'json'];

    public function __construct(private BotConfig $config, private Container $container, private TemporaryDocuments $files) {}

    public function key(): string
    {
        return 'parser';
    }

    public function listing(int $userId, int $page): Paginator
    {
        return $this->query($userId)->latest('id')->simplePaginate($this->config->integer('page_size'), ['*'], 'page', $page)
            ->through(fn (ParserRun $run): array => ['id' => $run->id, 'label' => ucfirst($run->module).' / '.$run->started_at?->format('d.m H:i'), 'formats' => self::FORMATS]);
    }

    public function document(int $userId, int $id, string $format, string $locale): BotDocument
    {
        $run = $this->query($userId)->find($id);
        if ($run === null || ! in_array($format, self::FORMATS, true)) {
            throw new ArtifactUnavailable;
        }

        return $this->withLocale($locale, function () use ($run, $userId, $format): BotDocument {
            /** @var ParserRunApplicationServiceInterface $service */
            $service = $this->container->make($this->config->get('parsers.'.$run->module.'.service'));
            $payload = $service->getDownloadPayload($userId, $run->run_id);
            $filename = $run->module.'-'.$run->run_id.'.'.$format;

            return $this->files->create($filename, function (string $path) use ($run, $payload, $format): void {
                if ($format === 'json') {
                    Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                } else {
                    /** @var ParserExportBuilderInterface $builder */
                    $builder = $this->container->make($this->config->get('parsers.'.$run->module.'.builder'));
                    Excel::store(new WorkbookExport($builder->buildSheets($payload)), $path, 'local', Writer::XLSX);
                }
            });
        });
    }

    private function query(int $userId): Builder
    {
        return ParserRun::query()->where('user_id', $userId)
            ->whereIn('module', array_keys($this->config->get('parsers', [])))
            ->whereIn('status', ParserRun::downloadableStatuses())
            ->whereNotNull('file_path')->where('expires_at', '>', now());
    }
}

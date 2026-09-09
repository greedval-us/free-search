<?php

namespace App\Modules\TelegramBot\Infrastructure\Artifacts;

use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Support\BotConfig;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final readonly class TemporaryDocuments
{
    public function __construct(private BotConfig $config) {}

    /** @param Closure(string): void $writer */
    public function create(string $filename, Closure $writer): BotDocument
    {
        $path = $this->config->get('temporary_directory').'/'.Str::uuid().'.'.pathinfo($filename, PATHINFO_EXTENSION);
        $disk = Storage::disk('local');
        try {
            $writer($path);
            if (! $disk->exists($path)) {
                throw new ArtifactUnavailable;
            }
            if ($disk->size($path) > $this->config->integer('max_document_bytes')) {
                throw new ArtifactUnavailable('file_too_large');
            }

            return new BotDocument($disk->path($path), $filename);
        } catch (Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }
    }

    public function remove(BotDocument $document): void
    {
        $root = realpath(Storage::disk('local')->path($this->config->get('temporary_directory')));
        $path = realpath($document->path);
        if ($root !== false && $path !== false && str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
            try {
                unlink($path);
            } catch (Throwable) {
                // Cleanup must not retry an already uploaded document. Scheduled pruning is the fallback.
                Log::warning('Telegram bot temporary document cleanup failed.');
            }
        }
    }
}

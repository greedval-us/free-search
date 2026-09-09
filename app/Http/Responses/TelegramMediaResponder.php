<?php

namespace App\Http\Responses;

use App\Http\Responses\Contracts\TelegramMediaResponderInterface;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final class TelegramMediaResponder implements TelegramMediaResponderInterface
{
    private const DEFAULT_DOWNLOAD_NAME = 'telegram-media';

    private const DEFAULT_MIME_TYPE = 'application/octet-stream';

    private const PRIVATE_CACHE_SECONDS = 3600;

    private const MAX_DOWNLOAD_NAME_LENGTH = 180;

    public function __construct(
        private readonly TelegramGatewayInterface $telegramGateway,
    ) {}

    public function respond(array $mediaPayload): BinaryFileResponse
    {
        $media = is_array($mediaPayload['media'] ?? null) ? $mediaPayload['media'] : null;
        abort_if($media === null, 404);

        $download = is_array($mediaPayload['download'] ?? null) ? $mediaPayload['download'] : [];
        $name = $this->normalizeDownloadName($download['name'] ?? null);
        $extension = $this->normalizeExtension($download['ext'] ?? null);
        $mimeType = $this->normalizeMimeType($download['mime'] ?? null);

        if ($extension !== '' && ! str_ends_with(strtolower($name), strtolower($extension))) {
            $name .= $extension;
        }

        $downloadPath = $this->createTemporaryFile($extension);
        try {
            $this->telegramGateway->downloadMediaToFile($media, $downloadPath);
        } catch (Throwable $exception) {
            File::delete($downloadPath);

            throw $exception;
        }

        return response()->file($downloadPath, [
            'Content-Type' => $mimeType !== '' ? $mimeType : self::DEFAULT_MIME_TYPE,
            'Content-Disposition' => sprintf(
                'inline; filename="%s"',
                addslashes($name !== '' ? $name : basename($downloadPath)),
            ),
            'Cache-Control' => sprintf('private, max-age=%d', self::PRIVATE_CACHE_SECONDS),
        ])->deleteFileAfterSend(true);
    }

    private function createTemporaryFile(string $extension): string
    {
        $directory = storage_path('app/private/telegram-media');
        File::ensureDirectoryExists($directory, 0755, true);

        $temporaryFile = tempnam($directory, 'tgm_');
        abort_if($temporaryFile === false, 500, __('errors.api.telegram.media_temp_file_failed'));

        if ($extension === '') {
            return $temporaryFile;
        }

        $downloadPath = $temporaryFile.$extension;
        if (! @rename($temporaryFile, $downloadPath)) {
            File::delete($temporaryFile);
            abort(500, __('errors.api.telegram.media_temp_file_failed'));
        }

        return $downloadPath;
    }

    private function normalizeDownloadName(mixed $name): string
    {
        $normalized = preg_replace(
            '/[\/\\\\\x00-\x1F\x7F]+/u',
            '-',
            trim((string) $name),
        ) ?? '';

        return Str::limit($normalized !== '' ? $normalized : self::DEFAULT_DOWNLOAD_NAME, self::MAX_DOWNLOAD_NAME_LENGTH, '');
    }

    private function normalizeExtension(mixed $extension): string
    {
        $normalized = strtolower(trim((string) $extension));

        return preg_match('/^\.[a-z0-9]{1,10}$/', $normalized) === 1 ? $normalized : '';
    }

    private function normalizeMimeType(mixed $mimeType): string
    {
        $normalized = strtolower(trim((string) $mimeType));

        return preg_match('#^[a-z0-9][a-z0-9.+-]*/[a-z0-9][a-z0-9.+-]*$#', $normalized) === 1
            ? $normalized
            : self::DEFAULT_MIME_TYPE;
    }
}

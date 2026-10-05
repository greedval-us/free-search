<?php

namespace App\Http\Responses;

use App\Http\Responses\Contracts\TelegramMediaResponderInterface;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Support\Http\DocumentResponseHeaders;
use finfo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

final class TelegramMediaResponder implements TelegramMediaResponderInterface
{
    private const DEFAULT_DOWNLOAD_NAME = 'telegram-media';

    private const DEFAULT_MIME_TYPE = 'application/octet-stream';

    private const INLINE_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/bmp',
        'video/mp4', 'video/webm', 'video/ogg', 'video/mpeg', 'video/quicktime',
        'audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/flac', 'audio/x-flac',
    ];

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

        $detectedMimeType = (new finfo(FILEINFO_MIME_TYPE))->file($downloadPath);
        $inline = in_array($detectedMimeType, self::INLINE_MIME_TYPES, true);

        return response()->file($downloadPath, [
            ...DocumentResponseHeaders::download(),
            'Content-Type' => $inline ? $detectedMimeType : self::DEFAULT_MIME_TYPE,
        ])->setContentDisposition(
            $inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $name,
        )->deleteFileAfterSend(true);
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
}

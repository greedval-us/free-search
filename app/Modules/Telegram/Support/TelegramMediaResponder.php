<?php

namespace App\Modules\Telegram\Support;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TelegramMediaResponder
{
    public function __construct(
        private readonly TelegramGatewayInterface $telegramService,
    ) {
    }

    public function respond(array $mediaPayload): BinaryFileResponse
    {
        $download = is_array($mediaPayload['download'] ?? null) ? $mediaPayload['download'] : [];
        $name = trim((string) ($download['name'] ?? 'telegram-media'));
        $ext = trim((string) ($download['ext'] ?? ''));
        $mime = trim((string) ($download['mime'] ?? 'application/octet-stream'));

        if ($ext !== '' && !str_ends_with(strtolower($name), strtolower($ext))) {
            $name .= $ext;
        }

        $directory = storage_path('app/private/telegram-media');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $tempFile = tempnam($directory, 'tgm_');
        if ($tempFile === false) {
            abort(500, 'Не удалось подготовить временный файл.');
        }

        $downloadPath = $tempFile;
        if ($ext !== '') {
            $downloadPath = $tempFile . $ext;
            @rename($tempFile, $downloadPath);
        }

        $this->telegramService->downloadMediaToFile($mediaPayload['media'], $downloadPath);

        return response()->file($downloadPath, [
            'Content-Type' => $mime !== '' ? $mime : 'application/octet-stream',
            'Content-Disposition' => sprintf('inline; filename="%s"', addslashes($name !== '' ? $name : basename($downloadPath))),
            'Cache-Control' => 'private, max-age=3600',
        ])->deleteFileAfterSend(true);
    }
}

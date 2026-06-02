<?php

namespace App\Http\Controllers\Mastodon;

use App\Http\Controllers\Concerns\HandlesParserDownloads;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedUserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mastodon\MastodonParserStartRequest;
use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\Mastodon\Parser\Contracts\MastodonParserApplicationServiceInterface;
use App\Modules\Mastodon\Parser\Contracts\MastodonParserExportBuilderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MastodonParserController extends Controller
{
    use HandlesParserDownloads;
    use ResolvesAuthenticatedUserId;

    public function __construct(
        private readonly MastodonParserApplicationServiceInterface $parserApplicationService,
        private readonly MastodonParserExportBuilderInterface $exportBuilder,
        private readonly ExcelWorkbookServiceInterface $excelWorkbookService,
    ) {
    }

    public function start(MastodonParserStartRequest $request): JsonResponse
    {
        return $this->jsonPayloadFrom($this->parserApplicationService->start($request->toStartDTO()));
    }

    public function status(Request $request, string $runId): JsonResponse
    {
        return $this->jsonPayloadFromOrNotFound(
            $this->parserApplicationService->status($this->userId($request), $runId)
        );
    }

    public function stop(Request $request, string $runId): JsonResponse
    {
        return $this->jsonPayloadFromOrNotFound(
            $this->parserApplicationService->stop($this->userId($request), $runId)
        );
    }

    public function downloadExcel(Request $request, string $runId): BinaryFileResponse
    {
        $payload = $this->parserApplicationService->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename(
            'mastodon-parser',
            (string) ($payload['account'] ?? 'account'),
            'xlsx'
        );

        return $this->excelWorkbookService->download($filename, $this->exportBuilder->buildSheets($payload));
    }

    public function downloadJson(Request $request, string $runId): StreamedResponse
    {
        $payload = $this->parserApplicationService->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename(
            'mastodon-parser',
            (string) ($payload['account'] ?? 'account'),
            'json'
        );

        return $this->streamJsonDownload($payload, $filename);
    }
}

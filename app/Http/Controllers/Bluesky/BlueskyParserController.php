<?php

namespace App\Http\Controllers\Bluesky;

use App\Http\Controllers\Parser\AbstractParserController;
use App\Http\Requests\Bluesky\BlueskyParserStartRequest;
use App\Modules\Bluesky\Parser\Contracts\BlueskyParserApplicationServiceInterface;
use App\Modules\Bluesky\Parser\Contracts\BlueskyParserExportBuilderInterface;
use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use Illuminate\Http\JsonResponse;

final class BlueskyParserController extends AbstractParserController
{
    private const EXPORT_PREFIX = 'bluesky-parser';

    private const EXPORT_TARGET_KEY = 'actor';

    private const EXPORT_TARGET_FALLBACK = 'actor';

    public function __construct(
        private readonly BlueskyParserApplicationServiceInterface $parserApplicationService,
        BlueskyParserExportBuilderInterface $exportBuilder,
        ExcelWorkbookServiceInterface $excelWorkbookService,
        ParserRunConfig $parserRunConfig,
        ReportFilenamePolicyInterface $filenamePolicy,
    ) {
        parent::__construct(
            $parserApplicationService,
            $exportBuilder,
            $excelWorkbookService,
            $parserRunConfig,
            $filenamePolicy,
            self::EXPORT_PREFIX,
            self::EXPORT_TARGET_KEY,
            self::EXPORT_TARGET_FALLBACK,
        );
    }

    public function start(BlueskyParserStartRequest $request): JsonResponse
    {
        return $this->jsonPayloadFrom($this->parserApplicationService->start($request->toStartDTO()));
    }
}

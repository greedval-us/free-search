<?php

namespace App\Http\Controllers\YouTube;

use App\Http\Controllers\Parser\AbstractParserController;
use App\Http\Requests\YouTube\YouTubeParserRequest;
use App\Http\Requests\YouTube\YouTubeParserStartRequest;
use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserExportBuilderInterface;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use Illuminate\Http\JsonResponse;

class YouTubeParserController extends AbstractParserController
{
    private const EXPORT_PREFIX = 'youtube-parser';

    private const EXPORT_TARGET_KEY = 'videoId';

    private const EXPORT_TARGET_FALLBACK = 'video';

    public function __construct(
        private readonly YouTubeParserApplicationServiceInterface $parserApplicationService,
        YouTubeParserExportBuilderInterface $exportBuilder,
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

    public function comments(YouTubeParserRequest $request): JsonResponse
    {
        return $this->commentsResponse($request);
    }

    public function commentsPreview(YouTubeParserRequest $request): JsonResponse
    {
        return $this->commentsResponse($request);
    }

    private function commentsResponse(YouTubeParserRequest $request): JsonResponse
    {
        return $this->jsonDataFrom($this->parserApplicationService->comments($request->toDTO()));
    }

    public function start(YouTubeParserStartRequest $request): JsonResponse
    {
        return $this->jsonPayloadFrom($this->parserApplicationService->start($request->toStartDTO()));
    }
}

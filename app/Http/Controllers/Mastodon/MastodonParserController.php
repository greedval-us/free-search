<?php

namespace App\Http\Controllers\Mastodon;

use App\Http\Controllers\Parser\AbstractParserController;
use App\Http\Requests\Mastodon\MastodonParserStartRequest;
use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\Mastodon\Parser\Contracts\MastodonParserApplicationServiceInterface;
use App\Modules\Mastodon\Parser\Contracts\MastodonParserExportBuilderInterface;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use Illuminate\Http\JsonResponse;

final class MastodonParserController extends AbstractParserController
{
    private const EXPORT_PREFIX = 'mastodon-parser';

    private const EXPORT_TARGET_KEY = 'account';

    private const EXPORT_TARGET_FALLBACK = 'account';

    public function __construct(
        private readonly MastodonParserApplicationServiceInterface $parserApplicationService,
        MastodonParserExportBuilderInterface $exportBuilder,
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

    public function start(MastodonParserStartRequest $request): JsonResponse
    {
        return $this->jsonPayloadFrom($this->parserApplicationService->start($request->toStartDTO()));
    }
}

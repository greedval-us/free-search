<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Parser\AbstractParserController;
use App\Http\Requests\Telegram\TelegramParserStartRequest;
use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserExportBuilderInterface;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use Illuminate\Http\JsonResponse;

class TelegramParserController extends AbstractParserController
{
    private const EXPORT_PREFIX = 'telegram-parser';

    private const EXPORT_TARGET_KEY = 'chatUsername';

    private const EXPORT_TARGET_FALLBACK = 'chat';

    public function __construct(
        private readonly TelegramParserApplicationServiceInterface $parserApplicationService,
        TelegramParserExportBuilderInterface $exportBuilder,
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

    public function start(TelegramParserStartRequest $request): JsonResponse
    {
        return $this->jsonPayloadFrom($this->parserApplicationService->start($request->toStartDTO()));
    }
}

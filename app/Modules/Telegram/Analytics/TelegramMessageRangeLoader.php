<?php

namespace App\Modules\Telegram\Analytics;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\Support\TelegramConfig;
use Carbon\Carbon;

class TelegramMessageRangeLoader
{
    public function __construct(
        private readonly TelegramGatewayInterface $telegramService,
        private readonly TelegramMessagePresenter $messagePresenter,
        private readonly TelegramConfig $config,
    ) {}

    /**
     * @return array<int, object>
     */
    public function load(string $chatUsername, Carbon $dateFrom, Carbon $dateTo, ?string $keyword = null): array
    {
        $messages = [];
        $seenIds = [];
        $seenOffsets = [];
        $offsetId = 0;
        $minDate = $dateFrom->timestamp;
        $maxDate = $dateTo->timestamp;
        $keyword = trim((string) $keyword);
        $maxPages = $this->config->analyticsFetchMaxPages();
        $pageLimit = $this->config->analyticsFetchPageLimit();

        for ($page = 0; $page < $maxPages; $page++) {
            $dto = $this->telegramService->getMessages([
                'peer' => $chatUsername,
                'q' => $keyword,
                'limit' => $pageLimit,
                'offset_id' => $offsetId,
                // Telegram search bounds are exclusive; the report includes both edge seconds.
                'min_date' => max(0, $minDate - 1),
                'max_date' => $maxDate + 1,
            ]);

            if ($dto === null || ($dto->inexact && empty($dto->messages))) {
                throw new ExternalServiceUnavailableException('errors.api.telegram.load_messages_failed', 'telegram_analytics_load_messages_failed');
            }
            if (empty($dto->messages)) {
                return $messages;
            }

            $oldestReached = false;

            foreach ($dto->messages as $message) {
                $messageId = (int) ($message->id ?? 0);
                $messageDate = (int) ($message->date ?? 0);

                if ($messageId <= 0) {
                    continue;
                }

                if ($messageDate < $minDate) {
                    $oldestReached = true;
                    break;
                }

                if ($messageDate > $maxDate) {
                    continue;
                }

                if (isset($seenIds[$messageId])) {
                    continue;
                }

                $seenIds[$messageId] = true;
                $messages[] = $message;
            }

            if ($oldestReached) {
                return $messages;
            }

            $nextOffsetId = $this->messagePresenter->resolveNextOffsetId($dto->messages);

            // Search may return short non-final pages. Only a confirmed end completes the range.
            if ($nextOffsetId === null || $nextOffsetId <= 0 || isset($seenOffsets[$nextOffsetId])
                || ($offsetId > 0 && $nextOffsetId >= $offsetId)) {
                throw new ExternalServiceRequestException('errors.api.telegram.load_messages_failed', 422, 'telegram_analytics_pagination_stalled');
            }

            $seenOffsets[$nextOffsetId] = true;
            $offsetId = $nextOffsetId;
        }

        throw new ExternalServiceRequestException('errors.api.telegram.load_messages_failed', 422, 'telegram_analytics_collection_limit');
    }
}

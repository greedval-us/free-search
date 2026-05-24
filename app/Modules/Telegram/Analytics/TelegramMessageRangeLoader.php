<?php

namespace App\Modules\Telegram\Analytics;

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
    ) {
    }

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
                'min_date' => $minDate,
                'max_date' => $maxDate,
            ]);

            if ($dto === null || empty($dto->messages)) {
                break;
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
                break;
            }

            $nextOffsetId = $this->messagePresenter->resolveNextOffsetId($dto->messages);

            if ($nextOffsetId === null || count($dto->messages) < $pageLimit) {
                break;
            }

            if (isset($seenOffsets[$nextOffsetId])) {
                break;
            }

            $seenOffsets[$nextOffsetId] = true;
            $offsetId = $nextOffsetId;
        }

        return $messages;
    }
}

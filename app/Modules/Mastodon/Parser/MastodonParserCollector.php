<?php

namespace App\Modules\Mastodon\Parser;

use App\Modules\Mastodon\DTO\Parser\MastodonParserStateDTO;
use App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface;
use App\Modules\Mastodon\Enums\MastodonParserStage;
use App\Modules\Mastodon\Presenters\MastodonAccountPresenter;
use App\Modules\Mastodon\Presenters\MastodonStatusPresenter;

final class MastodonParserCollector
{
    private const STATUSES_LIMIT = 20;

    public function __construct(
        private readonly MastodonGatewayInterface $gateway,
        private readonly MastodonAccountPresenter $accountPresenter,
        private readonly MastodonStatusPresenter $statusPresenter,
        private readonly MastodonParserSnapshotBuilder $snapshotBuilder,
    ) {
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function advance(array $run): array
    {
        $state = MastodonParserStateDTO::fromArray($run);

        if (! $state->isRunning()) {
            return $state->toArray();
        }

        return match ($state->stage()) {
            MastodonParserStage::Statuses => $this->advanceStatuses($state),
            MastodonParserStage::Comments => $this->advanceComments($state),
            MastodonParserStage::Finishing => $this->finish($state),
            default => $state->toArray(),
        };
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function buildResultSnapshot(array $run): array
    {
        $state = MastodonParserStateDTO::fromArray($run);

        return $this->snapshotBuilder->build($state->accountQuery(), $state->data())->toArray();
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceStatuses(MastodonParserStateDTO $state): array
    {
        $cursor = $state->cursor();
        $data = $state->data();

        $account = $this->resolveAccount($state);

        $payload = $this->gateway->accountStatuses(
            (string) ($account['id'] ?? ''),
            self::STATUSES_LIMIT,
            $cursor->statusesMaxId()
        );

        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $commentStatusIds = $cursor->commentStatusIds();

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $presented = $this->statusPresenter->present($item);

            if ($data->appendStatus($presented) && $data->shouldLoadCommentsForStatus($presented)) {
                $commentStatusIds[] = (string) ($presented['id'] ?? '');
            }
        }

        $cursor->incrementStatusesPage();

        $nextMaxId = (string) data_get($payload, 'pagination.nextMaxId', '');
        if ($nextMaxId !== '') {
            $cursor->setStatusesMaxId($nextMaxId);
            $totalHint = $cursor->statusesTotalHint();
            $state->setProgress($totalHint > 0
                ? min(70, max(2, (int) floor(($data->statusesCount() / $totalHint) * 70)))
                : min(70, 2 + ($cursor->statusesPage() * 3)));
        } else {
            $cursor->setStatusesMaxId(null);
            $cursor->setCommentStatusIds($commentStatusIds);
            $cursor->setCommentStatusIndex(0);
            $state->setStage(
                count($cursor->commentStatusIds()) > 0
                    ? MastodonParserStage::Comments
                    : MastodonParserStage::Finishing
            );
            $state->setProgress(count($cursor->commentStatusIds()) > 0 ? 75 : 95);
        }

        return $state->toArray();
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceComments(MastodonParserStateDTO $state): array
    {
        $cursor = $state->cursor();
        $data = $state->data();

        $commentStatusIds = $cursor->commentStatusIds();
        $statusIndex = $cursor->commentStatusIndex();
        if ($statusIndex >= count($commentStatusIds)) {
            $state->setStage(MastodonParserStage::Finishing);
            $state->setProgress(95);

            return $state->toArray();
        }

        $rootStatusId = (string) $commentStatusIds[$statusIndex];
        $payload = $this->gateway->context($rootStatusId);
        $descendants = is_array($payload['descendants'] ?? null) ? $payload['descendants'] : [];

        foreach ($descendants as $item) {
            if (! is_array($item)) {
                continue;
            }

            $data->appendComment($rootStatusId, $this->statusPresenter->present($item));
        }

        $processedRoots = min($cursor->incrementCommentStatusIndex(), count($commentStatusIds));
        $totalRoots = max(1, count($commentStatusIds));
        $state->setProgress(min(99, 75 + (int) floor(($processedRoots / $totalRoots) * 24)));

        if ($processedRoots >= count($commentStatusIds)) {
            $state->setStage(MastodonParserStage::Finishing);
            $state->setProgress(95);
        }

        return $state->toArray();
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function finish(MastodonParserStateDTO $state): array
    {
        $state->complete(
            result: $this->snapshotBuilder->build($state->accountQuery(), $state->data())->toArray(),
            stage: MastodonParserStage::Completed,
        );

        return $state->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveAccount(MastodonParserStateDTO $state): array
    {
        $data = $state->data();
        $cursor = $state->cursor();
        $account = $data->account();

        if ($account !== null) {
            return $account;
        }

        $lookup = $this->gateway->lookupAccount($state->accountQuery());
        $account = $this->accountPresenter->present($lookup);
        $data->setAccount($account);
        $cursor->setStatusesTotalHint((int) ($account['statusesCount'] ?? 0));

        return $account;
    }
}

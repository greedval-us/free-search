<?php

namespace App\Modules\Mastodon\Parser;

use App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface;
use App\Modules\Mastodon\Enums\MastodonPostType;
use App\Modules\Mastodon\Parser\Enums\MastodonParserStage;
use App\Modules\Mastodon\Presenters\MastodonAccountPresenter;
use App\Modules\Mastodon\Presenters\MastodonStatusPresenter;

final class MastodonParserCollector
{
    private const STATUSES_LIMIT = 20;

    public function __construct(
        private readonly MastodonGatewayInterface $gateway,
        private readonly MastodonAccountPresenter $accountPresenter,
        private readonly MastodonStatusPresenter $statusPresenter,
    ) {
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function advance(array $run): array
    {
        if (($run['status'] ?? null) !== 'running') {
            return $run;
        }

        $stage = MastodonParserStage::tryFrom((string) ($run['stage'] ?? ''))
            ?? MastodonParserStage::Statuses;

        return match ($stage) {
            MastodonParserStage::Statuses => $this->advanceStatuses($run),
            MastodonParserStage::Comments => $this->advanceComments($run),
            MastodonParserStage::Finishing => $this->finish($run),
            default => $run,
        };
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function buildResultSnapshot(array $run): array
    {
        $context = is_array($run['context'] ?? null) ? $run['context'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];

        return [
            'account' => (string) ($context['account'] ?? ''),
            'resolvedAccount' => is_array($data['account'] ?? null) ? $data['account'] : null,
            'statusesCount' => (int) ($stats['processedStatuses'] ?? 0),
            'commentsCount' => (int) ($stats['processedComments'] ?? 0),
            'statusesIndex' => array_values(is_array($data['statusesIndex'] ?? null) ? $data['statusesIndex'] : []),
            'commentsIndex' => array_values(is_array($data['commentsIndex'] ?? null) ? $data['commentsIndex'] : []),
        ];
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceStatuses(array $run): array
    {
        $context = is_array($run['context'] ?? null) ? $run['context'] : [];
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];

        $account = $this->resolveAccount($context, $data, $cursor);

        $payload = $this->gateway->accountStatuses(
            (string) ($account['id'] ?? ''),
            self::STATUSES_LIMIT,
            $this->nullIfEmpty((string) ($cursor['statusesMaxId'] ?? ''))
        );

        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $statusIds = is_array($data['statusIds'] ?? null) ? $data['statusIds'] : [];
        $statusesIndex = is_array($data['statusesIndex'] ?? null) ? $data['statusesIndex'] : [];
        $commentStatusIds = is_array($cursor['commentStatusIds'] ?? null) ? array_values($cursor['commentStatusIds']) : [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $presented = $this->statusPresenter->present($item);

            if ($this->appendStatus($presented, $statusIds, $statusesIndex)) {
                $this->appendCommentRootStatusId($presented, $commentStatusIds);
            }
        }

        $stats['processedStatuses'] = count($statusesIndex);
        $cursor['statusesPage'] = (int) ($cursor['statusesPage'] ?? 0) + 1;

        $nextMaxId = (string) data_get($payload, 'pagination.nextMaxId', '');
        if ($nextMaxId !== '') {
            $cursor['statusesMaxId'] = $nextMaxId;
            $totalHint = max(0, (int) ($cursor['statusesTotalHint'] ?? 0));
            $run['progress'] = $totalHint > 0
                ? min(70, max(2, (int) floor((count($statusesIndex) / $totalHint) * 70)))
                : min(70, 2 + ((int) $cursor['statusesPage'] * 3));
        } else {
            $cursor['statusesMaxId'] = '';
            $cursor['commentStatusIds'] = array_values(array_unique($commentStatusIds));
            $cursor['commentStatusIndex'] = 0;
            $run['stage'] = count($cursor['commentStatusIds']) > 0
                ? MastodonParserStage::Comments->value
                : MastodonParserStage::Finishing->value;
            $run['progress'] = count($cursor['commentStatusIds']) > 0 ? 75 : 95;
        }

        $data['statusIds'] = $statusIds;
        $data['statusesIndex'] = $statusesIndex;
        $run['cursor'] = $cursor;
        $run['data'] = $data;
        $run['stats'] = $stats;

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceComments(array $run): array
    {
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];

        $commentStatusIds = is_array($cursor['commentStatusIds'] ?? null) ? array_values($cursor['commentStatusIds']) : [];
        $statusIndex = (int) ($cursor['commentStatusIndex'] ?? 0);
        if ($statusIndex >= count($commentStatusIds)) {
            $run['stage'] = MastodonParserStage::Finishing->value;
            $run['progress'] = 95;

            return $run;
        }

        $rootStatusId = (string) $commentStatusIds[$statusIndex];
        $payload = $this->gateway->context($rootStatusId);
        $descendants = is_array($payload['descendants'] ?? null) ? $payload['descendants'] : [];
        $commentIds = is_array($data['commentIds'] ?? null) ? $data['commentIds'] : [];
        $commentsIndex = is_array($data['commentsIndex'] ?? null) ? $data['commentsIndex'] : [];

        foreach ($descendants as $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->appendComment(
                rootStatusId: $rootStatusId,
                presented: $this->statusPresenter->present($item),
                commentIds: $commentIds,
                commentsIndex: $commentsIndex,
            );
        }

        $cursor['commentStatusIndex'] = $statusIndex + 1;
        $processedRoots = min((int) ($cursor['commentStatusIndex'] ?? 0), count($commentStatusIds));
        $totalRoots = max(1, count($commentStatusIds));
        $stats['processedComments'] = count($commentsIndex);
        $run['progress'] = min(99, 75 + (int) floor(($processedRoots / $totalRoots) * 24));

        if ($processedRoots >= count($commentStatusIds)) {
            $run['stage'] = MastodonParserStage::Finishing->value;
            $run['progress'] = 95;
        }

        $data['commentIds'] = $commentIds;
        $data['commentsIndex'] = $commentsIndex;
        $run['cursor'] = $cursor;
        $run['data'] = $data;
        $run['stats'] = $stats;

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function finish(array $run): array
    {
        $run['result'] = $this->buildResultSnapshot($run);
        $run['status'] = 'completed';
        $run['stage'] = MastodonParserStage::Completed->value;
        $run['progress'] = 100;
        $run['error'] = null;

        return $run;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     * @param array<string, mixed> $cursor
     * @return array<string, mixed>
     */
    private function resolveAccount(array $context, array &$data, array &$cursor): array
    {
        $account = is_array($data['account'] ?? null) ? $data['account'] : null;

        if ($account !== null) {
            return $account;
        }

        $lookup = $this->gateway->lookupAccount((string) ($context['account'] ?? ''));
        $account = $this->accountPresenter->present($lookup);
        $data['account'] = $account;
        $cursor['statusesTotalHint'] = (int) ($account['statusesCount'] ?? 0);

        return $account;
    }

    /**
     * @param array<string, mixed> $presented
     * @param array<string, bool> $statusIds
     * @param array<int, array<string, mixed>> $statusesIndex
     */
    private function appendStatus(array $presented, array &$statusIds, array &$statusesIndex): bool
    {
        $statusId = (string) ($presented['id'] ?? '');

        if ($statusId === '' || isset($statusIds[$statusId])) {
            return false;
        }

        $statusIds[$statusId] = true;
        $statusesIndex[] = $presented;

        return true;
    }

    /**
     * @param array<string, mixed> $presented
     * @param array<int, string> $commentStatusIds
     */
    private function appendCommentRootStatusId(array $presented, array &$commentStatusIds): void
    {
        if (
            (string) ($presented['postType'] ?? '') !== MastodonPostType::Original->value
            || (int) ($presented['repliesCount'] ?? 0) <= 0
        ) {
            return;
        }

        $statusId = (string) ($presented['id'] ?? '');

        if ($statusId !== '') {
            $commentStatusIds[] = $statusId;
        }
    }

    /**
     * @param array<string, mixed> $presented
     * @param array<string, bool> $commentIds
     * @param array<int, array<string, mixed>> $commentsIndex
     */
    private function appendComment(
        string $rootStatusId,
        array $presented,
        array &$commentIds,
        array &$commentsIndex,
    ): void {
        $commentId = (string) ($presented['id'] ?? '');

        if ($commentId === '') {
            return;
        }

        $compositeId = $rootStatusId . ':' . $commentId;

        if (isset($commentIds[$compositeId])) {
            return;
        }

        $commentIds[$compositeId] = true;
        $commentsIndex[] = [
            'rootStatusId' => $rootStatusId,
            'commentId' => $commentId,
            'parentStatusId' => $presented['inReplyToId'] ?? null,
            'createdAt' => (string) ($presented['createdAt'] ?? ''),
            'content' => (string) ($presented['content'] ?? ''),
            'spoilerText' => (string) ($presented['spoilerText'] ?? ''),
            'language' => (string) ($presented['language'] ?? ''),
            'visibility' => (string) ($presented['visibility'] ?? ''),
            'sensitive' => (bool) ($presented['sensitive'] ?? false),
            'repliesCount' => (int) ($presented['repliesCount'] ?? 0),
            'reblogsCount' => (int) ($presented['reblogsCount'] ?? 0),
            'favouritesCount' => (int) ($presented['favouritesCount'] ?? 0),
            'hasMedia' => (bool) ($presented['hasMedia'] ?? false),
            'hasLinks' => (bool) ($presented['hasLinks'] ?? false),
            'postType' => (string) ($presented['postType'] ?? ''),
            'url' => (string) ($presented['url'] ?? ''),
            'links' => is_array($presented['links'] ?? null) ? $presented['links'] : [],
            'domains' => is_array($presented['domains'] ?? null) ? $presented['domains'] : [],
            'tags' => is_array($presented['tags'] ?? null) ? $presented['tags'] : [],
            'mentions' => is_array($presented['mentions'] ?? null) ? $presented['mentions'] : [],
            'account' => is_array($presented['account'] ?? null) ? $presented['account'] : [],
        ];
    }

    private function nullIfEmpty(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}

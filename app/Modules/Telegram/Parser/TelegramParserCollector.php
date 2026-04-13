<?php

namespace App\Modules\Telegram\Parser;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\Presenters\TelegramCommentPresenter;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;

class TelegramParserCollector
{
    private const MESSAGE_LIMIT = 50;
    private const COMMENT_LIMIT = 20;

    public function __construct(
        private readonly TelegramGatewayInterface $telegramService,
        private readonly TelegramMessagePresenter $messagePresenter,
        private readonly TelegramCommentPresenter $commentPresenter,
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

        $stage = (string) ($run['stage'] ?? 'messages');

        return match ($stage) {
            'messages' => $this->advanceMessages($run),
            'comments' => $this->advanceComments($run),
            'finishing' => $this->finish($run),
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
        $messages = is_array($data['messages'] ?? null) ? $data['messages'] : [];
        $commentsIndex = is_array($data['commentsIndex'] ?? null) ? $data['commentsIndex'] : [];
        $reactionsIndex = is_array($data['reactionsIndex'] ?? null) ? $data['reactionsIndex'] : [];

        return [
            'chatUsername' => (string) ($context['chatUsername'] ?? ''),
            'period' => (string) ($context['period'] ?? ''),
            'keyword' => (string) ($context['keyword'] ?? ''),
            'range' => [
                'dateFrom' => (string) (($context['range']['dateFrom'] ?? '') ?: ''),
                'dateTo' => (string) (($context['range']['dateTo'] ?? '') ?: ''),
            ],
            'isChannel' => (bool) ($data['isChannel'] ?? false),
            'messagesCount' => (int) ($stats['processedMessages'] ?? count($messages)),
            'commentsCount' => (int) ($stats['processedComments'] ?? count($commentsIndex)),
            'messages' => $messages,
            'commentsIndex' => $commentsIndex,
            'reactionsIndex' => $reactionsIndex,
        ];
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceMessages(array $run): array
    {
        $context = is_array($run['context'] ?? null) ? $run['context'] : [];
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];

        $filter = [
            'peer' => (string) ($context['chatUsername'] ?? ''),
            'q' => (string) ($context['keyword'] ?? ''),
            'limit' => self::MESSAGE_LIMIT,
            'offset_id' => (int) ($cursor['messagesOffsetId'] ?? 0),
        ];

        $keyword = trim((string) ($context['keyword'] ?? ''));
        $range = is_array($context['range'] ?? null) ? $context['range'] : [];
        if ($keyword === '' && !empty($range['minTimestamp'])) {
            $filter['min_date'] = (int) $range['minTimestamp'];
        }
        if ($keyword === '' && !empty($range['maxTimestamp'])) {
            $filter['max_date'] = (int) $range['maxTimestamp'];
        }

        $dto = $this->telegramService->getMessages($filter);
        if ($dto === null) {
            return $this->fail($run, __('Failed to load messages for parser.'));
        }

        $cursor['messagesPage'] = (int) ($cursor['messagesPage'] ?? 0) + 1;
        $cursor['messagesTotalHint'] = (int) ($dto->count ?? ($cursor['messagesTotalHint'] ?? 0));
        $messageIds = is_array($data['messageIds'] ?? null) ? $data['messageIds'] : [];
        $messages = is_array($data['messages'] ?? null) ? $data['messages'] : [];
        $reactionsIndex = is_array($data['reactionsIndex'] ?? null) ? $data['reactionsIndex'] : [];

        $presented = $this->messagePresenter->presentMessages($dto->messages ?? [], (string) ($context['chatUsername'] ?? ''));
        foreach ($presented as $message) {
            if (!is_array($message)) {
                continue;
            }

            $messageId = (int) ($message['id'] ?? 0);
            if ($messageId <= 0 || isset($messageIds[$messageId])) {
                continue;
            }

            $messageIds[$messageId] = true;
            $messages[] = $message;
            foreach (($message['reactions'] ?? []) as $reaction) {
                $reactionsIndex[] = [
                    'entityType' => 'message',
                    'messageId' => $messageId,
                    'commentId' => null,
                    'reactionKey' => (string) ($reaction['key'] ?? ''),
                    'reaction' => (string) ($reaction['emoji'] ?? ''),
                    'count' => (int) ($reaction['count'] ?? 0),
                    'senderIds' => is_array($reaction['senderIds'] ?? null) ? array_map('intval', $reaction['senderIds']) : [],
                ];
            }
        }

        $stats['processedMessages'] = count($messages);
        $nextOffsetId = $this->messagePresenter->resolveNextOffsetId($dto->messages ?? []);
        $hasMore = $nextOffsetId !== null && count($dto->messages ?? []) >= self::MESSAGE_LIMIT;

        if ($nextOffsetId === null || !$hasMore) {
            $info = $this->telegramService->getInfo((string) ($context['chatUsername'] ?? ''));
            $isChannel = (bool) ($info?->chat?->broadcast ?? false);
            $commentPostIds = [];

            if ($isChannel) {
                foreach ($messages as $message) {
                    $repliesCount = (int) ($message['repliesCount'] ?? 0);
                    if ($repliesCount > 0) {
                        $commentPostIds[] = (int) ($message['id'] ?? 0);
                    }
                }
            }

            $data['isChannel'] = $isChannel;
            $cursor['commentPostIds'] = array_values(array_filter($commentPostIds, static fn (int $id): bool => $id > 0));
            $cursor['commentPostIndex'] = 0;
            $cursor['commentOffsetId'] = 0;
            $run['stage'] = count($cursor['commentPostIds']) > 0 ? 'comments' : 'finishing';
            $run['progress'] = count($cursor['commentPostIds']) > 0 ? 70 : 95;
        } else {
            $cursor['messagesOffsetId'] = $nextOffsetId;
            $totalHint = max(0, (int) ($cursor['messagesTotalHint'] ?? 0));
            if ($totalHint > 0) {
                $run['progress'] = min(70, max(2, (int) floor((count($messages) / $totalHint) * 70)));
            } else {
                $run['progress'] = min(70, 2 + ((int) $cursor['messagesPage'] * 4));
            }
        }

        $data['messages'] = $messages;
        $data['messageIds'] = $messageIds;
        $data['reactionsIndex'] = $reactionsIndex;
        $run['data'] = $data;
        $run['cursor'] = $cursor;
        $run['stats'] = $stats;

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceComments(array $run): array
    {
        $context = is_array($run['context'] ?? null) ? $run['context'] : [];
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];

        $postIds = is_array($cursor['commentPostIds'] ?? null) ? array_map('intval', $cursor['commentPostIds']) : [];
        $postIndex = (int) ($cursor['commentPostIndex'] ?? 0);
        if ($postIndex >= count($postIds)) {
            $run['stage'] = 'finishing';
            $run['progress'] = 95;

            return $run;
        }

        $postId = $postIds[$postIndex];
        $offsetId = (int) ($cursor['commentOffsetId'] ?? 0);

        $page = $this->telegramService->getComments(
            (string) ($context['chatUsername'] ?? ''),
            $postId,
            self::COMMENT_LIMIT,
            $offsetId
        );
        $presented = $this->commentPresenter->present($page, self::COMMENT_LIMIT, $offsetId);

        $commentIds = is_array($data['commentIds'] ?? null) ? $data['commentIds'] : [];
        $commentsIndex = is_array($data['commentsIndex'] ?? null) ? $data['commentsIndex'] : [];
        $reactionsIndex = is_array($data['reactionsIndex'] ?? null) ? $data['reactionsIndex'] : [];
        $items = is_array($presented['items'] ?? null) ? $presented['items'] : [];

        foreach ($items as $comment) {
            if (!is_array($comment)) {
                continue;
            }

            $commentId = (int) ($comment['id'] ?? 0);
            if ($commentId <= 0) {
                continue;
            }

            $compositeId = $postId . ':' . $commentId;
            if (isset($commentIds[$compositeId])) {
                continue;
            }

            $commentIds[$compositeId] = true;
            $comment['postId'] = $postId;
            $commentsIndex[] = $comment;
            $stats['processedComments'] = (int) ($stats['processedComments'] ?? 0) + 1;

            foreach (($comment['reactions'] ?? []) as $reaction) {
                $reactionsIndex[] = [
                    'entityType' => 'comment',
                    'messageId' => $postId,
                    'commentId' => $commentId,
                    'reactionKey' => (string) ($reaction['key'] ?? ''),
                    'reaction' => (string) ($reaction['emoji'] ?? ''),
                    'count' => (int) ($reaction['count'] ?? 0),
                    'senderIds' => is_array($reaction['senderIds'] ?? null) ? array_map('intval', $reaction['senderIds']) : [],
                ];
            }
        }

        $hasMore = (bool) ($presented['pagination']['hasMore'] ?? false);
        $nextOffsetId = (int) ($presented['pagination']['nextOffsetId'] ?? 0);

        if ($hasMore && $nextOffsetId > 0) {
            $cursor['commentOffsetId'] = $nextOffsetId;
        } else {
            $cursor['commentPostIndex'] = $postIndex + 1;
            $cursor['commentOffsetId'] = 0;
        }

        $processedPosts = min((int) ($cursor['commentPostIndex'] ?? 0), count($postIds));
        $totalPosts = max(1, count($postIds));
        $run['progress'] = min(99, 70 + (int) floor(($processedPosts / $totalPosts) * 29));
        if ($processedPosts >= count($postIds)) {
            $run['stage'] = 'finishing';
            $run['progress'] = 95;
        }

        $data['commentIds'] = $commentIds;
        $data['commentsIndex'] = $commentsIndex;
        $data['reactionsIndex'] = $reactionsIndex;
        $run['data'] = $data;
        $run['cursor'] = $cursor;
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
        $run['stage'] = 'completed';
        $run['progress'] = 100;
        $run['error'] = null;

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function fail(array $run, string $message): array
    {
        $run['status'] = 'failed';
        $run['stage'] = 'failed';
        $run['progress'] = 100;
        $run['error'] = $message;

        return $run;
    }
}

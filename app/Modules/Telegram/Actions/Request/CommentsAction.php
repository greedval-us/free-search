<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Access\PublicTelegramSource;
use App\Modules\Telegram\Actions\AbstractTelegramAction;
use Closure;
use RuntimeException;

class CommentsAction extends AbstractTelegramAction
{
    public function execute(
        string $channelId,
        array $postIds,
        int $delayMs = 500,
        int $commentsPerRequest = 100,
        int $maxPages = 10,
        int $offsetId = 0
    ): array {
        $results = [];
        $client = $this->madeline();
        $postIds = array_values(
            array_filter(
                array_unique(array_map('intval', $postIds)),
                static fn (int $id): bool => $id > 0
            )
        );

        $commentsPerRequest = max(1, min($commentsPerRequest, 100));
        $maxPages = max(1, $maxPages);
        $delayMs = max(0, $delayMs);

        foreach ($postIds as $postId) {
            try {
                $source = $this->publicSource($client, $channelId);
                if ($source === null) {
                    throw new RuntimeException('Telegram source is unavailable.');
                }
                $discussion = PublicTelegramSource::resolveDiscussion(
                    $source,
                    fn (array $peer): array => $this->executeWithRetry(fn () => $client->getFullInfo($peer), ['channel' => $channelId]),
                    fn (int $id): array => $client->getInfo($id),
                    fn (string $username): array => $client->contacts->resolveUsername(['username' => $username]),
                );
                if ($discussion === null) {
                    throw new RuntimeException('Telegram discussion is unavailable.');
                }

                $post = $this->executeWithRetry(
                    callback: fn () => $client->channels->getMessages([
                        'channel' => $source['channel'],
                        'id' => [$postId],
                    ]),
                    context: ['channel' => $channelId, 'post_id' => $postId]
                );

                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }

                $commentsPage = $this->loadComments(
                    request: fn (array $parameters): array => $client->messages->getReplies($parameters),
                    peer: $source['peer'],
                    postId: $postId,
                    limit: $commentsPerRequest,
                    maxPages: $maxPages,
                    offsetId: $offsetId,
                    delayMs: $delayMs,
                );

                $results[] = [
                    'post_id' => $postId,
                    'post' => $post,
                    'comments' => $commentsPage['messages'],
                    'next_offset_id' => $commentsPage['next_offset_id'],
                    'has_more' => $commentsPage['has_more'],
                    'total' => $commentsPage['total'],
                ];
            } catch (\Throwable $e) {
                $this->logError($e, ['channel' => $channelId, 'post_id' => $postId]);

                $results[] = [
                    'post_id' => $postId,
                    'error' => 'comments_unavailable',
                    'comments' => [],
                    'next_offset_id' => null,
                    'has_more' => false,
                    'total' => 0,
                ];
            }
        }

        return $results;
    }

    protected function loadComments(
        Closure $request,
        array $peer,
        int $postId,
        int $limit,
        int $maxPages,
        int $offsetId,
        int $delayMs
    ): array {
        $messages = [];
        $nextOffsetId = $offsetId;
        $hasMore = false;
        $total = 0;

        for ($page = 1; $page <= $maxPages; $page++) {
            $response = $this->executeWithRetry(
                callback: fn () => $request([
                    'peer' => $peer,
                    'msg_id' => $postId,
                    'offset_id' => $nextOffsetId,
                    'offset_date' => 0,
                    'add_offset' => 0,
                    'limit' => $limit,
                    'max_id' => 0,
                    'min_id' => 0,
                    'hash' => 0,
                ]),
                context: ['post_id' => $postId, 'page' => $page]
            );

            if (! in_array($response['_'] ?? '', ['messages.messages', 'messages.messagesSlice', 'messages.channelMessages'], true)
                || ! is_array($response['messages'] ?? null) || ! array_is_list($response['messages'])) {
                throw new RuntimeException('Telegram returned an invalid comments page.');
            }
            $batch = $response['messages'];
            foreach ($batch as $message) {
                if (! is_array($message) || (int) ($message['id'] ?? 0) <= 0) {
                    throw new RuntimeException('Telegram returned an invalid comment.');
                }
            }
            $total = (int) ($response['count'] ?? $total);

            if (empty($batch)) {
                $hasMore = false;
                break;
            }

            foreach ($batch as $message) {
                $messages[] = $message;
            }

            $ids = array_values(array_filter(array_map(
                static fn ($message): int => (int) (is_array($message) ? ($message['id'] ?? 0) : ($message->id ?? 0)),
                $batch,
            ), static fn (int $id): bool => $id > 0));
            $previousOffsetId = $nextOffsetId;
            $nextOffsetId = $ids === [] ? 0 : min($ids);
            if ($nextOffsetId <= 0 || ($previousOffsetId > 0 && $nextOffsetId >= $previousOffsetId)) {
                throw new RuntimeException('Telegram comment pagination did not advance.');
            }

            $hasMore = true;

            if ($page >= $maxPages) {
                break;
            }

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        return [
            'messages' => $messages,
            'next_offset_id' => $hasMore ? $nextOffsetId : null,
            'has_more' => $hasMore,
            'total' => $total,
        ];
    }
}

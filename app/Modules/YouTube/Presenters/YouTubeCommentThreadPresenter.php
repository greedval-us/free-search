<?php

namespace App\Modules\YouTube\Presenters;

use Illuminate\Support\Arr;

class YouTubeCommentThreadPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(array $item): array
    {
        $snippet = Arr::get($item, 'snippet.topLevelComment.snippet', []);

        return [
            'id' => (string) Arr::get($item, 'snippet.topLevelComment.id', $item['id'] ?? ''),
            'threadId' => (string) ($item['id'] ?? ''),
            'videoId' => (string) Arr::get($item, 'snippet.videoId', ''),
            'author' => (string) Arr::get($snippet, 'authorDisplayName', ''),
            'authorChannelUrl' => (string) Arr::get($snippet, 'authorChannelUrl', ''),
            'text' => (string) Arr::get($snippet, 'textDisplay', ''),
            'likeCount' => (int) Arr::get($snippet, 'likeCount', 0),
            'publishedAt' => (string) Arr::get($snippet, 'publishedAt', ''),
            'updatedAt' => (string) Arr::get($snippet, 'updatedAt', ''),
            'replyCount' => (int) Arr::get($item, 'snippet.totalReplyCount', 0),
            'replies' => collect(Arr::get($item, 'replies.comments', []))
                ->map(fn (array $reply): array => [
                    'id' => (string) ($reply['id'] ?? ''),
                    'author' => (string) Arr::get($reply, 'snippet.authorDisplayName', ''),
                    'text' => (string) Arr::get($reply, 'snippet.textDisplay', ''),
                    'likeCount' => (int) Arr::get($reply, 'snippet.likeCount', 0),
                    'publishedAt' => (string) Arr::get($reply, 'snippet.publishedAt', ''),
                ])
                ->values()
                ->all(),
        ];
    }
}

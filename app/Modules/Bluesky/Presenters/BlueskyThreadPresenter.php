<?php

namespace App\Modules\Bluesky\Presenters;

use App\Modules\Bluesky\DTO\Result\BlueskyThreadResultDTO;

final class BlueskyThreadPresenter
{
    public function __construct(
        private readonly BlueskyPostPresenter $postPresenter,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function present(array $payload, string $uri, int $depth, int $parentHeight): BlueskyThreadResultDTO
    {
        $thread = (array) ($payload['thread'] ?? []);
        $root = $this->presentThreadNode($thread);

        return new BlueskyThreadResultDTO(
            root: $root,
            ancestors: $this->collectAncestors($thread),
            replies: $this->collectReplies($thread),
            meta: [
                'uri' => $uri,
                'depth' => $depth,
                'parentHeight' => $parentHeight,
            ],
        );
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>|null
     */
    private function presentThreadNode(array $node): ?array
    {
        $post = (array) ($node['post'] ?? []);

        if ($post === []) {
            return null;
        }

        return [
            ...$this->postPresenter->present($post),
            'replies' => collect($node['replies'] ?? [])
                ->map(fn (array $reply): ?array => $this->presentThreadNode($reply))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    /**
     * @param array<string, mixed> $node
     * @return array<int, array<string, mixed>>
     */
    private function collectAncestors(array $node): array
    {
        $ancestors = [];
        $current = $node;

        while (is_array($current['parent'] ?? null)) {
            $parent = (array) $current['parent'];
            $post = (array) ($parent['post'] ?? []);

            if ($post !== []) {
                array_unshift($ancestors, $this->postPresenter->present($post));
            }

            $current = $parent;
        }

        return $ancestors;
    }

    /**
     * @param array<string, mixed> $node
     * @return array<int, array<string, mixed>>
     */
    private function collectReplies(array $node): array
    {
        return collect($node['replies'] ?? [])
            ->map(fn (array $reply): ?array => $this->presentThreadNode($reply))
            ->filter()
            ->values()
            ->all();
    }
}

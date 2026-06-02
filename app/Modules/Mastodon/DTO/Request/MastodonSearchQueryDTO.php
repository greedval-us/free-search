<?php

namespace App\Modules\Mastodon\DTO\Request;

final readonly class MastodonSearchQueryDTO
{
    public function __construct(
        public string $query,
        public string $type,
        public int $limit,
        public int $offset = 0,
        public bool $resolve = false,
        public string $language = '',
        public ?bool $hasMedia = null,
        public ?bool $hasLinks = null,
        public ?bool $hasReplies = null,
        public string $instanceDomain = '',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $params = [
            'q' => $this->query,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];

        if ($this->type !== 'all') {
            $params['type'] = $this->type;
        }

        if ($this->resolve) {
            $params['resolve'] = 'true';
        }

        return $params;
    }
}

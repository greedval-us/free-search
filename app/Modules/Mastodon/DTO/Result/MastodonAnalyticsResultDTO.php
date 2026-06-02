<?php

namespace App\Modules\Mastodon\DTO\Result;

use App\Support\Contracts\ArrayPayloadable;

final readonly class MastodonAnalyticsResultDTO implements ArrayPayloadable
{
    /**
     * @param array<string, mixed>|null $profile
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $summary
     * @param array<int, array<string, mixed>> $timeline
     * @param array<int, array<string, mixed>> $topDomains
     * @param array<int, array<string, mixed>> $topTags
     * @param array<int, array<string, mixed>> $topAccounts
     * @param array<int, array<string, mixed>> $topMentions
     * @param array<int, array<string, mixed>> $topLanguages
     * @param array<int, array<string, mixed>> $topPosts
     */
    public function __construct(
        public ?array $profile,
        public array $meta,
        public array $summary,
        public array $timeline,
        public array $topDomains,
        public array $topTags,
        public array $topAccounts,
        public array $topMentions,
        public array $topLanguages,
        public array $topPosts,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'profile' => $this->profile,
            'meta' => $this->meta,
            'summary' => $this->summary,
            'timeline' => $this->timeline,
            'topDomains' => $this->topDomains,
            'topTags' => $this->topTags,
            'topAccounts' => $this->topAccounts,
            'topMentions' => $this->topMentions,
            'topLanguages' => $this->topLanguages,
            'topPosts' => $this->topPosts,
        ];
    }
}

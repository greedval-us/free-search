<?php

namespace Tests\Unit;

use App\Support\Activity\RequestLogRunUrlBuilder;
use Tests\TestCase;

class RequestLogRunUrlBuilderTest extends TestCase
{
    public function test_it_builds_mastodon_search_repeat_url(): void
    {
        $url = app(RequestLogRunUrlBuilder::class)->build(
            path: '/mastodon/search',
            method: 'GET',
            payload: [
                'q' => 'osint',
                'type' => 'statuses',
                'limit' => 10,
                'resolve' => 'true',
                'author' => '@analyst',
            ],
        );

        $this->assertSame(
            '/mastodon?tab=search&q=osint&type=statuses&limit=10&resolve=true&author=%40analyst&autorun=1',
            $url
        );
    }

    public function test_it_builds_bluesky_analytics_repeat_url(): void
    {
        $url = app(RequestLogRunUrlBuilder::class)->build(
            path: '/bluesky/analytics/summary',
            method: 'GET',
            payload: [
                'mode' => 'account',
                'target' => '@investigator.bsky.social',
                'limit' => 12,
                'pages' => 4,
                'resolve' => 'false',
            ],
        );

        $this->assertSame(
            '/bluesky?tab=analytics&mode=account&target=%40investigator.bsky.social&limit=12&pages=4&resolve=false&autorun=1',
            $url
        );
    }

    public function test_it_returns_null_for_unmapped_auxiliary_requests(): void
    {
        $url = app(RequestLogRunUrlBuilder::class)->build(
            path: '/mastodon/accounts/123/followers',
            method: 'GET',
            payload: [
                'limit' => 10,
            ],
        );

        $this->assertNull($url);
    }

    public function test_it_returns_null_for_non_get_requests(): void
    {
        $url = app(RequestLogRunUrlBuilder::class)->build(
            path: '/telegram/parser/start',
            method: 'POST',
            payload: [
                'chatUsername' => 'channel',
            ],
        );

        $this->assertNull($url);
    }
}

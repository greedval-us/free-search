<?php

namespace Tests\Feature\TelegramBot;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;

final class ReportDeliveryStatusTest extends TelegramBotTestCase
{
    #[DataProvider('reportIndexes')]
    public function test_report_indexes_use_the_same_link_and_export_preferences(string $url): void
    {
        $link = $this->linkedUser(preferences: ['exports_enabled' => false]);

        $this->actingAs($link->user)->getJson($url)->assertOk()
            ->assertJsonPath('data.botLinked', true)
            ->assertJsonPath('data.botExportsEnabled', false);

        $link->update(['exports_enabled' => true]);
        $this->getJson($url)->assertOk()
            ->assertJsonPath('data.botLinked', true)
            ->assertJsonPath('data.botExportsEnabled', true);

        // An outdated Telegram identity must not appear as a usable bot link.
        $link->user->update(['telegram_id' => '20002']);
        $this->getJson($url)->assertOk()
            ->assertJsonPath('data.botLinked', false)
            ->assertJsonPath('data.botExportsEnabled', true);

        $this->actingAs(User::factory()->create())->getJson($url)->assertOk()
            ->assertJsonPath('data.botLinked', false)
            ->assertJsonPath('data.botExportsEnabled', false);
    }

    public static function reportIndexes(): array
    {
        return [
            'Telegram' => ['/telegram/analytics/reports'],
            'YouTube' => ['/youtube/analytics/reports'],
            'Bluesky' => ['/bluesky/analytics/reports'],
            'Mastodon' => ['/mastodon/analytics/reports'],
            'Site Intel' => ['/site-intel/reports'],
            'News and media' => ['/news-media-intel/reports'],
        ];
    }
}

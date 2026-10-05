<?php

namespace Tests\Feature;

use App\Http\Requests\Telegram\SearchCommentsRequest;
use App\Http\Requests\Telegram\SearchMessagesRequest;
use App\Http\Requests\Telegram\StreamTelegramMediaRequest;
use App\Http\Requests\Telegram\TelegramAnalyticsRequest;
use App\Http\Requests\Telegram\TelegramParserStartRequest;
use App\Http\Requests\Telegram\TelegramTrackingRequest;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TelegramPublicAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::post('/_test/tg/messages', fn (SearchMessagesRequest $request) => $request->telegramFilter());
        Route::post('/_test/tg/comments', fn (SearchCommentsRequest $request) => $request->validated());
        Route::post('/_test/tg/analytics', fn (TelegramAnalyticsRequest $request) => $request->validated());
        Route::post('/_test/tg/parser', fn (TelegramParserStartRequest $request) => $request->validated());
        Route::get('/_test/tg/media/{chatUsername}/{messageId}', fn (StreamTelegramMediaRequest $request) => $request->validated());
        Route::post('/_test/tg/tracking', fn (TelegramTrackingRequest $request) => $request->validated())->name('telegram.tracking.validate');
    }

    public static function rejectedSources(): array
    {
        return [['-100123456789'], ['123456789'], ['+79990000000'], ['me'], ['tg://resolve?domain=channel'], ['https://t.me/+invite']];
    }

    #[DataProvider('rejectedSources')]
    public function test_search_comments_analytics_and_parser_return_422_for_private_identifiers(string $source): void
    {
        foreach (['messages', 'comments', 'analytics', 'parser'] as $endpoint) {
            $this->postJson('/_test/tg/'.$endpoint, ['chatUsername' => $source, 'postId' => 1, 'period' => 'week'])
                ->assertUnprocessable()->assertJsonValidationErrors('chatUsername');
        }
    }

    public function test_media_validates_route_parameters_and_ignores_query_parameter_substitution(): void
    {
        $this->getJson('/_test/tg/media/-100123/1?chatUsername=publicgroup')->assertUnprocessable()->assertJsonValidationErrors('chatUsername');
        $this->getJson('/_test/tg/media/publicgroup/not-a-number?messageId=1')->assertUnprocessable()->assertJsonValidationErrors('messageId');
        $this->getJson('/_test/tg/media/publicgroup/0')->assertUnprocessable()->assertJsonValidationErrors('messageId');
        $this->getJson('/_test/tg/media/publicgroup/42')->assertOk()->assertJsonPath('messageId', '42');
    }

    public function test_tracking_returns_422_for_saved_private_peer_identifiers(): void
    {
        $this->actingAs(User::factory()->make())->postJson('/_test/tg/tracking', ['groups' => ['-100123456789']])
            ->assertUnprocessable()->assertJsonValidationErrors('groups.0');
    }

    public function test_author_usernames_are_local_filters_without_session_peer_resolution(): void
    {
        $this->postJson('/_test/tg/messages', ['chatUsername' => '@publicgroup', 'fromUsername' => '@author'])
            ->assertOk()->assertJsonPath('authorUsername', 'author')->assertJsonMissingPath('from_id');
    }
}

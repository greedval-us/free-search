<?php

namespace Tests\Feature;

use App\Http\Requests\Telegram\TelegramAnalyticsRequest;
use App\Http\Requests\Telegram\TelegramParserStartRequest;
use App\Modules\Telegram\Support\TelegramConfig;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TelegramDateValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('osint.telegram.parser.custom_range_max_days', 3);
        config()->set('osint.telegram.analytics.custom_range_max_days', 3);
        config()->set('app.timezone', 'Europe/Berlin');
        app()->forgetInstance(TelegramConfig::class);
        Route::post('/_test/telegram-parser-dates', fn (TelegramParserStartRequest $request) => response()->json($request->range()));
        Route::post('/_test/telegram-analytics-dates', fn (TelegramAnalyticsRequest $request) => response()->json($request->validated()));
    }

    public static function ranges(): array
    {
        return [
            'same day' => ['2026-09-01', '2026-09-01', 200],
            'inclusive limit' => ['2026-09-01', '2026-09-03', 200],
            'daylight saving boundary' => ['2026-03-28', '2026-03-30', 200],
            'one day over limit' => ['2026-09-01', '2026-09-04', 422],
            'multi year range' => ['2010-01-01', '2026-09-01', 422],
            'reversed range' => ['2026-09-03', '2026-09-01', 422],
            'invalid date' => ['not-a-date', '2026-09-01', 422],
            'nonexistent date' => ['2026-02-30', '2026-03-01', 422],
            'array instead of date' => [['2026-09-01'], '2026-09-03', 422],
            'missing end date' => ['2026-09-01', null, 422],
        ];
    }

    #[DataProvider('ranges')]
    public function test_parser_and_analytics_validate_ranges(mixed $from, ?string $to, int $status): void
    {
        $input = ['chatUsername' => 'example', 'period' => 'custom', 'dateFrom' => $from, 'dateTo' => $to];
        $this->postJson('/_test/telegram-parser-dates', $input)->assertStatus($status);
        $this->postJson('/_test/telegram-analytics-dates', $input)->assertStatus($status);
    }

    public function test_single_day_limit_allows_the_whole_day(): void
    {
        config()->set('osint.telegram.parser.custom_range_max_days', 1);
        config()->set('osint.telegram.analytics.custom_range_max_days', 1);
        app()->forgetInstance(TelegramConfig::class);
        $input = ['chatUsername' => 'example', 'period' => 'custom', 'dateFrom' => '2026-09-01', 'dateTo' => '2026-09-01'];
        $response = $this->postJson('/_test/telegram-parser-dates', $input)->assertOk();
        $this->assertSame(86399, $response->json('maxTimestamp') - $response->json('minTimestamp'));
        $this->postJson('/_test/telegram-analytics-dates', $input)->assertOk();
    }
}

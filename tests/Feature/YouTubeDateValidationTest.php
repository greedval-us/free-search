<?php

namespace Tests\Feature;

use App\Http\Requests\YouTube\YouTubeAnalyticsRequest;
use App\Modules\YouTube\Support\YouTubeModuleConfig;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class YouTubeDateValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('osint.youtube.analytics_custom_range_max_days', 3);
        app()->forgetInstance(YouTubeModuleConfig::class);
        Route::post('/_test/youtube-dates', fn (YouTubeAnalyticsRequest $request) => response()->json($request->validated()));
    }

    public static function ranges(): array
    {
        return [
            'inclusive limit' => ['2026-09-01', '2026-09-03', 200],
            'over limit' => ['2026-09-01', '2026-09-04', 422],
            'reversed range' => ['2026-09-03', '2026-09-01', 422],
            'invalid date' => ['not-a-date', '2026-09-01', 422],
            'array instead of date' => [['2026-09-01'], '2026-09-03', 422],
            'missing end date' => ['2026-09-01', null, 422],
        ];
    }

    #[DataProvider('ranges')]
    public function test_dates_are_validated_for_explicit_and_inferred_channel_mode(mixed $from, ?string $to, int $status): void
    {
        $input = ['channelId' => 'example', 'dateFrom' => $from, 'dateTo' => $to];
        $this->postJson('/_test/youtube-dates', $input)->assertStatus($status);
        $this->postJson('/_test/youtube-dates', [...$input, 'mode' => 'channel'])->assertStatus($status);
    }

    public function test_configured_single_day_limit_is_respected(): void
    {
        config()->set('osint.youtube.analytics_custom_range_max_days', 1);
        app()->forgetInstance(YouTubeModuleConfig::class);
        $input = ['mode' => 'channel', 'channelId' => 'example', 'dateFrom' => '2026-09-01'];
        $this->postJson('/_test/youtube-dates', [...$input, 'dateTo' => '2026-09-01'])->assertOk();
        $this->postJson('/_test/youtube-dates', [...$input, 'dateTo' => '2026-09-02'])->assertUnprocessable();
    }
}

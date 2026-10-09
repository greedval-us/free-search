<?php

use App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController;
use App\Http\Controllers\NewsMediaIntel\NewsMediaIntelController;
use App\Support\Http\RouteThrottle;
use Illuminate\Support\Facades\Route;

Route::inertia('news-media-intel', 'NewsMediaIntel')->name('news-media-intel');

Route::prefix('news-media-intel')->name('news-media-intel.')->group(function (): void {
    Route::get('options', [NewsMarketingAnalyticsController::class, 'options'])->middleware(RouteThrottle::DETAIL_LOOKUP)->name('options');
    Route::post('analytics', [NewsMarketingAnalyticsController::class, 'analytics'])->middleware(RouteThrottle::ANALYTICS_SUMMARY)->name('analytics');
    Route::get('report/{reportId}', [NewsMarketingAnalyticsController::class, 'report'])->whereUuid('reportId')->middleware(RouteThrottle::ANALYTICS_REPORT)->name('report');
    Route::get('lookup', [NewsMediaIntelController::class, 'lookup'])
        ->middleware(RouteThrottle::STANDARD_SEARCH)
        ->name('lookup');
});

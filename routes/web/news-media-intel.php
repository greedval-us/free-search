<?php

use App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController;
use App\Http\Controllers\NewsMediaIntel\NewsMediaIntelController;
use App\Http\Controllers\NewsMediaIntel\NewsMediaReportsController;
use App\Support\Http\RouteThrottle;
use Illuminate\Support\Facades\Route;

Route::inertia('news-media-intel', 'NewsMediaIntel')->name('news-media-intel');

Route::prefix('news-media-intel')->name('news-media-intel.')->group(function (): void {
    Route::prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/', [NewsMediaReportsController::class, 'index'])->middleware(RouteThrottle::PARSER_STATUS)->name('index');
        Route::post('schedules', [NewsMediaReportsController::class, 'store'])->middleware(RouteThrottle::PARSER_CONTROL)->name('store');
        Route::patch('schedules/{schedule}', [NewsMediaReportsController::class, 'change'])->whereNumber('schedule')->middleware(RouteThrottle::PARSER_CONTROL)->name('change');
        Route::post('schedules/{schedule}/run', [NewsMediaReportsController::class, 'runNow'])->whereNumber('schedule')->middleware(RouteThrottle::PARSER_START)->name('run');
        Route::delete('schedules/{schedule}', [NewsMediaReportsController::class, 'destroy'])->whereNumber('schedule')->middleware(RouteThrottle::PARSER_CONTROL)->name('destroy');
        Route::get('{report}/view', [NewsMediaReportsController::class, 'view'])->whereNumber('report')->middleware(RouteThrottle::ANALYTICS_REPORT)->name('view');
        Route::get('{report}/download/{format}', [NewsMediaReportsController::class, 'download'])->whereNumber('report')->whereIn('format', ['html', 'json'])->middleware(RouteThrottle::PARSER_DOWNLOAD)->name('download');
    });
    Route::get('options', [NewsMarketingAnalyticsController::class, 'options'])->middleware(RouteThrottle::DETAIL_LOOKUP)->name('options');
    Route::post('analytics', [NewsMarketingAnalyticsController::class, 'analytics'])->middleware(RouteThrottle::ANALYTICS_SUMMARY)->name('analytics');
    Route::get('report/{reportId}', [NewsMarketingAnalyticsController::class, 'report'])->whereUuid('reportId')->middleware(RouteThrottle::ANALYTICS_REPORT)->name('report');
    Route::get('lookup', [NewsMediaIntelController::class, 'lookup'])
        ->middleware(RouteThrottle::STANDARD_SEARCH)
        ->name('lookup');
});

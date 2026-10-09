<?php

use App\Http\Controllers\SiteIntel\SiteIntelController;
use App\Http\Controllers\SiteIntel\SiteIntelReportsController;
use App\Http\Middleware\EnsureSiteIntelReportsAccess;
use App\Support\Http\RouteThrottle;
use Illuminate\Support\Facades\Route;

Route::inertia('site-intel', 'SiteIntel')
    ->middleware(['feature.access', EnsureSiteIntelReportsAccess::class])
    ->name('site-intel');

Route::prefix('site-intel')->name('site-intel.')->group(function (): void {
    Route::prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/', [SiteIntelReportsController::class, 'index'])->middleware([EnsureSiteIntelReportsAccess::class, RouteThrottle::PARSER_STATUS])->name('index');
        Route::post('schedules', [SiteIntelReportsController::class, 'store'])->middleware(RouteThrottle::PARSER_CONTROL)->name('store');
        Route::patch('schedules/{schedule}', [SiteIntelReportsController::class, 'change'])->whereNumber('schedule')->middleware(RouteThrottle::PARSER_CONTROL)->name('change');
        Route::post('schedules/{schedule}/run', [SiteIntelReportsController::class, 'runNow'])->whereNumber('schedule')->middleware(RouteThrottle::PARSER_START)->name('run');
        Route::delete('schedules/{schedule}', [SiteIntelReportsController::class, 'destroy'])->whereNumber('schedule')->middleware(RouteThrottle::PARSER_CONTROL)->name('destroy');
        Route::get('{report}/view', [SiteIntelReportsController::class, 'view'])->whereNumber('report')->middleware(RouteThrottle::SITE_REPORT)->name('view');
        Route::get('{report}/download/{format}', [SiteIntelReportsController::class, 'download'])->whereNumber('report')->whereIn('format', ['html', 'json'])->middleware(RouteThrottle::PARSER_DOWNLOAD)->name('download');
    });
    Route::get('site-health', [SiteIntelController::class, 'siteHealth'])
        ->middleware(RouteThrottle::SITE_LOOKUP)
        ->name('site-health');

    Route::get('domain-lite', [SiteIntelController::class, 'domainLite'])
        ->middleware(RouteThrottle::SITE_LOOKUP)
        ->name('domain-lite');

    Route::get('analytics', [SiteIntelController::class, 'analytics'])
        ->middleware(['feature.access', RouteThrottle::SITE_ANALYTICS])
        ->name('analytics');

    Route::get('seo-audit', [SiteIntelController::class, 'seoAudit'])
        ->middleware(['feature.access', RouteThrottle::SITE_ANALYTICS])
        ->name('seo-audit');

    Route::get('seo-report', [SiteIntelController::class, 'seoReport'])
        ->middleware(['feature.access', RouteThrottle::SITE_REPORT])
        ->name('seo-report');

    Route::get('report', [SiteIntelController::class, 'report'])
        ->middleware(['feature.access', RouteThrottle::SITE_REPORT])
        ->name('report');
});

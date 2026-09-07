<?php

use App\Http\Controllers\SiteIntel\SiteIntelController;
use App\Support\Http\RouteThrottle;
use Illuminate\Support\Facades\Route;

Route::inertia('site-intel', 'SiteIntel')
    ->middleware('feature.access')
    ->name('site-intel');

Route::prefix('site-intel')->name('site-intel.')->group(function (): void {
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

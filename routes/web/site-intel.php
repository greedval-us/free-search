<?php

use App\Http\Controllers\SiteIntel\SiteIntelController;
use Illuminate\Support\Facades\Route;

Route::inertia('site-intel', 'SiteIntel')->name('site-intel');

Route::prefix('site-intel')->name('site-intel.')->group(function (): void {
    Route::get('site-health', [SiteIntelController::class, 'siteHealth'])
        ->middleware('throttle:90,1')
        ->name('site-health');

    Route::get('domain-lite', [SiteIntelController::class, 'domainLite'])
        ->middleware('throttle:90,1')
        ->name('domain-lite');

    Route::get('analytics', [SiteIntelController::class, 'analytics'])
        ->middleware('throttle:60,1')
        ->name('analytics');

    Route::get('report', [SiteIntelController::class, 'report'])
        ->middleware('throttle:30,1')
        ->name('report');
});

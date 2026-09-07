<?php

use App\Http\Controllers\NewsMediaIntel\NewsMediaIntelController;
use App\Support\Http\RouteThrottle;
use Illuminate\Support\Facades\Route;

Route::inertia('news-media-intel', 'NewsMediaIntel')->name('news-media-intel');

Route::prefix('news-media-intel')->name('news-media-intel.')->group(function (): void {
    Route::get('lookup', [NewsMediaIntelController::class, 'lookup'])
        ->middleware(RouteThrottle::STANDARD_SEARCH)
        ->name('lookup');
});

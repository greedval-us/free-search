<?php

use App\Http\Controllers\NewsMediaIntel\NewsMediaIntelController;
use Illuminate\Support\Facades\Route;

Route::inertia('news-media-intel', 'NewsMediaIntel')->name('news-media-intel');

Route::prefix('news-media-intel')->name('news-media-intel.')->group(function (): void {
    Route::get('lookup', [NewsMediaIntelController::class, 'lookup'])
        ->middleware('throttle:30,1')
        ->name('lookup');
});


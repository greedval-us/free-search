<?php

use App\Http\Controllers\EmailIntel\EmailIntelController;
use Illuminate\Support\Facades\Route;

Route::inertia('email-intel', 'EmailIntel')->name('email-intel');

Route::prefix('email-intel')->name('email-intel.')->group(function (): void {
    Route::get('lookup', [EmailIntelController::class, 'lookup'])
        ->middleware('throttle:60,1')
        ->name('lookup');

    Route::get('bulk', [EmailIntelController::class, 'bulk'])
        ->middleware('throttle:20,1')
        ->name('bulk');

    Route::get('domain-posture', [EmailIntelController::class, 'domainPosture'])
        ->middleware('throttle:40,1')
        ->name('domain-posture');

    Route::get('report', [EmailIntelController::class, 'report'])
        ->middleware('throttle:20,1')
        ->name('report');
});

<?php

use App\Http\Controllers\EmailIntel\EmailIntelController;
use Illuminate\Support\Facades\Route;

Route::inertia('email-intel', 'EmailIntel')->name('email-intel');

Route::prefix('email-intel')->name('email-intel.')->group(function (): void {
    Route::get('lookup', [EmailIntelController::class, 'lookup'])
        ->middleware('throttle:60,1')
        ->name('lookup');
});

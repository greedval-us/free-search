<?php

use App\Http\Controllers\Username\UsernameSearchController;
use Illuminate\Support\Facades\Route;

Route::inertia('username', 'Username')->name('username');

Route::prefix('username')->name('username.')->group(function (): void {
    Route::get('search', [UsernameSearchController::class, 'search'])
        ->middleware('throttle:90,1')
        ->name('search');

    Route::get('report', [UsernameSearchController::class, 'report'])
        ->middleware('throttle:10,1')
        ->name('report');
});

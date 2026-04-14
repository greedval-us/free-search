<?php

use App\Http\Controllers\Username\UsernameSearchController;
use Illuminate\Support\Facades\Route;

Route::inertia('username', 'Username')->name('username');

Route::prefix('username')->name('username.')->group(function (): void {
    Route::get('search', [UsernameSearchController::class, 'search'])
        ->middleware('throttle:90,1')
        ->name('search');
});

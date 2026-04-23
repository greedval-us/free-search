<?php

use App\Http\Controllers\Dorks\DorkSearchController;
use Illuminate\Support\Facades\Route;

Route::inertia('dorks', 'Dorks')->name('dorks');

Route::prefix('dorks')->name('dorks.')->group(function (): void {
    Route::get('search', [DorkSearchController::class, 'search'])
        ->middleware('throttle:25,1')
        ->name('search');

    Route::get('goals', [DorkSearchController::class, 'goals'])
        ->middleware('throttle:60,1')
        ->name('goals');

    Route::get('report', [DorkSearchController::class, 'report'])
        ->middleware('throttle:10,1')
        ->name('report');
});


<?php

use App\Http\Controllers\Shifr\ShifrController;
use Illuminate\Support\Facades\Route;

Route::inertia('shifr', 'Shifr')->name('shifr');

Route::prefix('shifr')->name('shifr.')->group(function (): void {
    Route::get('hash', [ShifrController::class, 'hash'])
        ->middleware('throttle:90,1')
        ->name('hash');

    Route::get('transform', [ShifrController::class, 'transform'])
        ->middleware('throttle:90,1')
        ->name('transform');

    Route::get('ioc-extract', [ShifrController::class, 'extractIocs'])
        ->middleware('throttle:90,1')
        ->name('ioc-extract');

    Route::get('classic', [ShifrController::class, 'classic'])
        ->middleware('throttle:90,1')
        ->name('classic');
});

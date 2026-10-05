<?php

use App\Http\Controllers\Shifr\ShifrController;
use App\Support\Http\RouteThrottle;
use Illuminate\Support\Facades\Route;

Route::inertia('shifr', 'Shifr')->name('shifr');

Route::prefix('shifr')->name('shifr.')->group(function (): void {
    Route::post('hash', [ShifrController::class, 'hash'])
        ->middleware(RouteThrottle::TOOL_OPERATION)
        ->name('hash');

    Route::post('transform', [ShifrController::class, 'transform'])
        ->middleware(RouteThrottle::TOOL_OPERATION)
        ->name('transform');

    Route::post('ioc-extract', [ShifrController::class, 'extractIocs'])
        ->middleware(RouteThrottle::TOOL_OPERATION)
        ->name('ioc-extract');

    Route::post('jwt-inspect', [ShifrController::class, 'inspectJwt'])
        ->middleware(RouteThrottle::TOOL_OPERATION)
        ->name('jwt-inspect');

    Route::post('classic', [ShifrController::class, 'classic'])
        ->middleware(RouteThrottle::TOOL_OPERATION)
        ->name('classic');
});

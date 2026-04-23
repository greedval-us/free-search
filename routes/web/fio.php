<?php

use App\Http\Controllers\Fio\FioLookupController;
use Illuminate\Support\Facades\Route;

Route::inertia('fio', 'Fio')->name('fio');

Route::prefix('fio')->name('fio.')->group(function (): void {
    Route::get('lookup', [FioLookupController::class, 'lookup'])
        ->middleware('throttle:60,1')
        ->name('lookup');
});

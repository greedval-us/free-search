<?php

use App\Http\Controllers\DocumentIntel\DocumentIntelController;
use Illuminate\Support\Facades\Route;

Route::inertia('document-intel', 'DocumentIntel')->name('document-intel');

Route::prefix('document-intel')->name('document-intel.')->group(function (): void {
    Route::get('lookup', [DocumentIntelController::class, 'lookup'])
        ->middleware('throttle:60,1')
        ->name('lookup');
});

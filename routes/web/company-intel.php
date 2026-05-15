<?php

use App\Http\Controllers\CompanyIntel\CompanyIntelController;
use Illuminate\Support\Facades\Route;

Route::inertia('company-intel', 'CompanyIntel')->name('company-intel');

Route::prefix('company-intel')->name('company-intel.')->group(function (): void {
    Route::get('lookup', [CompanyIntelController::class, 'lookup'])
        ->middleware('throttle:60,1')
        ->name('lookup');
});

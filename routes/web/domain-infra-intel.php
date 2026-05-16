<?php

use App\Http\Controllers\DomainInfraIntel\DomainInfraIntelController;
use Illuminate\Support\Facades\Route;

Route::inertia('domain-infra-intel', 'DomainInfraIntel')->name('domain-infra-intel');

Route::prefix('domain-infra-intel')->name('domain-infra-intel.')->group(function (): void {
    Route::get('lookup', [DomainInfraIntelController::class, 'lookup'])
        ->middleware('throttle:30,1')
        ->name('lookup');
});


<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dashboard\ModulePinController;
use App\Http\Controllers\Dashboard\SavedQueryController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('dashboard/saved-queries', [SavedQueryController::class, 'store'])
        ->name('dashboard.saved-queries.store');
    Route::delete('dashboard/saved-queries/{savedQuery}', [SavedQueryController::class, 'destroy'])
        ->name('dashboard.saved-queries.destroy');
    Route::post('dashboard/module-pins/toggle', [ModulePinController::class, 'toggle'])
        ->name('dashboard.module-pins.toggle');

    require __DIR__.'/web/telegram.php';
    require __DIR__.'/web/username.php';
    require __DIR__.'/web/site-intel.php';
    require __DIR__.'/web/fio.php';
    require __DIR__.'/web/email-intel.php';
});

require __DIR__.'/settings.php';

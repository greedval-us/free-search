<?php

use App\Http\Controllers\TelegramSearchController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('telegram', 'Telegram')->name('telegram');
    Route::get('telegram/search/messages', [TelegramSearchController::class, 'messages'])->name('telegram.search.messages');
});

require __DIR__.'/settings.php';

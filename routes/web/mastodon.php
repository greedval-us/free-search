<?php

use App\Http\Controllers\Mastodon\MastodonSearchController;
use Illuminate\Support\Facades\Route;

Route::inertia('mastodon', 'Mastodon')
    ->middleware('feature.access')
    ->name('mastodon');

Route::prefix('mastodon')->name('mastodon.')->group(function (): void {
    Route::prefix('search')->name('search.')->group(function (): void {
        Route::get('', [MastodonSearchController::class, 'search'])
            ->middleware('throttle:45,1')
            ->name('index');
    });
});

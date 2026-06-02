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

    Route::get('statuses/{statusId}/context', [MastodonSearchController::class, 'context'])
        ->middleware('throttle:60,1')
        ->name('statuses.context');

    Route::get('accounts/{accountId}/statuses', [MastodonSearchController::class, 'accountStatuses'])
        ->middleware('throttle:60,1')
        ->name('accounts.statuses');

    Route::get('accounts/{accountId}/followers', [MastodonSearchController::class, 'accountFollowers'])
        ->middleware('throttle:60,1')
        ->name('accounts.followers');
});

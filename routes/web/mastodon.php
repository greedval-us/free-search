<?php

use App\Http\Controllers\Mastodon\MastodonAnalyticsController;
use App\Http\Controllers\Mastodon\MastodonSearchController;
use Illuminate\Support\Facades\Route;

Route::inertia('mastodon', 'Mastodon')
    ->middleware('feature.access')
    ->name('mastodon');

Route::prefix('mastodon')->name('mastodon.')->group(function (): void {
    Route::prefix('analytics')->name('analytics.')->group(function (): void {
        Route::get('summary', [MastodonAnalyticsController::class, 'summary'])
            ->middleware(['feature.access', 'throttle:30,1'])
            ->name('summary');

        Route::get('report', [MastodonAnalyticsController::class, 'report'])
            ->middleware(['feature.access', 'throttle:20,1'])
            ->name('report');
    });

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

    Route::get('tags/{tagName}/statuses', [MastodonSearchController::class, 'tagTimeline'])
        ->middleware('throttle:60,1')
        ->name('tags.statuses');
});

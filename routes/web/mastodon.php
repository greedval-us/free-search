<?php

use App\Http\Controllers\Mastodon\MastodonAnalyticsController;
use App\Http\Controllers\Mastodon\MastodonParserController;
use App\Http\Controllers\Mastodon\MastodonSearchController;
use App\Support\Http\RouteThrottle;
use Illuminate\Support\Facades\Route;

Route::inertia('mastodon', 'Mastodon')
    ->middleware('feature.access')
    ->name('mastodon');

Route::prefix('mastodon')->name('mastodon.')->group(function (): void {
    Route::prefix('analytics')->name('analytics.')->group(function (): void {
        Route::get('summary', [MastodonAnalyticsController::class, 'summary'])
            ->middleware(['feature.access', RouteThrottle::FEDIVERSE_ANALYTICS_SUMMARY])
            ->name('summary');

        Route::get('report', [MastodonAnalyticsController::class, 'report'])
            ->middleware(['feature.access', RouteThrottle::FEDIVERSE_ANALYTICS_REPORT])
            ->name('report');
    });

    Route::prefix('search')->name('search.')->group(function (): void {
        Route::get('', [MastodonSearchController::class, 'search'])
            ->middleware(RouteThrottle::FEDIVERSE_SEARCH)
            ->name('index');
    });

    Route::prefix('parser')->name('parser.')->group(function (): void {
        Route::post('start', [MastodonParserController::class, 'start'])
            ->middleware(['feature.access', RouteThrottle::PARSER_START])
            ->name('start');
        Route::get('status/{runId}', [MastodonParserController::class, 'status'])
            ->middleware(['feature.access', RouteThrottle::PARSER_STATUS])
            ->name('status');
        Route::post('stop/{runId}', [MastodonParserController::class, 'stop'])
            ->middleware(['feature.access', RouteThrottle::PARSER_CONTROL])
            ->name('stop');
        Route::get('history', [MastodonParserController::class, 'history'])
            ->middleware(['feature.access', RouteThrottle::PARSER_CONTROL])
            ->name('history');
        Route::get('download-excel/{runId}', [MastodonParserController::class, 'downloadExcel'])
            ->middleware(['feature.access', RouteThrottle::PARSER_DOWNLOAD])
            ->name('download-excel');
        Route::get('download-json/{runId}', [MastodonParserController::class, 'downloadJson'])
            ->middleware(['feature.access', RouteThrottle::PARSER_DOWNLOAD])
            ->name('download-json');
    });

    Route::get('statuses/{statusId}/context', [MastodonSearchController::class, 'context'])
        ->middleware(RouteThrottle::DETAIL_LOOKUP)
        ->name('statuses.context');

    Route::get('accounts/{accountId}/statuses', [MastodonSearchController::class, 'accountStatuses'])
        ->middleware(RouteThrottle::DETAIL_LOOKUP)
        ->name('accounts.statuses');

    Route::get('accounts/{accountId}/followers', [MastodonSearchController::class, 'accountFollowers'])
        ->middleware(RouteThrottle::DETAIL_LOOKUP)
        ->name('accounts.followers');

    Route::get('tags/{tagName}/statuses', [MastodonSearchController::class, 'tagTimeline'])
        ->middleware(RouteThrottle::DETAIL_LOOKUP)
        ->name('tags.statuses');
});

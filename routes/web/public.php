<?php

use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('features', [PublicSiteController::class, 'index'])->name('features.index');
Route::get('features/{feature}', [PublicSiteController::class, 'show'])
    ->where('feature', '[a-z-]+')->name('features.show');

Route::inertia('privacy', 'Privacy')->name('privacy');
Route::inertia('terms', 'Terms')->name('terms');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

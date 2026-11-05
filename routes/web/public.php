<?php

use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('features', [PublicSiteController::class, 'index'])->name('features.index');
Route::get('features/{feature}', [PublicSiteController::class, 'show'])
    ->where('feature', '[a-z-]+')->name('features.show');

Route::get('privacy', static fn () => Inertia::render('Privacy', [
    'contactEmail' => config('app.privacy_contact_email'),
]))->name('privacy');
Route::inertia('terms', 'Terms')->name('terms');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

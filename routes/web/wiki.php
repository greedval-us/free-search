<?php

use App\Http\Controllers\Wiki\ModuleWikiController;
use Illuminate\Support\Facades\Route;

Route::get('wiki/modules', ModuleWikiController::class)->name('wiki.modules');


<?php

declare(strict_types=1);

use App\Http\Controllers\MoonShine\TelegramSessionController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::moonshine(static function (Router $router): void {
    $router->middleware([...moonshineConfig()->getAuthMiddleware(), 'throttle:telegram-session-auth'])
        ->prefix('telegram-sessions')->name('telegram-sessions.')
        ->group(static function (Router $router): void {
            $router->post('/', TelegramSessionController::class)->name('store');
            foreach (['phone', 'code', 'password', 'inspect'] as $action) {
                $router->post('/{connection}/'.$action, TelegramSessionController::class)
                    ->whereNumber('connection')->name($action);
            }
        });
});

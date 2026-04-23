<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class BaseTelegramController extends Controller
{
    protected function userId(Request $request): int
    {
        return (int) $request->user()->id;
    }
}

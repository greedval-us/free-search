<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Concerns\ResolvesAuthenticatedUserId;
use App\Http\Controllers\Controller;

abstract class BaseTelegramController extends Controller
{
    use ResolvesAuthenticatedUserId;
}

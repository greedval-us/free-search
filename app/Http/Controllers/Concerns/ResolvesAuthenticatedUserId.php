<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesAuthenticatedUserId
{
    protected function userId(Request $request): int
    {
        return (int) $request->user()->id;
    }
}


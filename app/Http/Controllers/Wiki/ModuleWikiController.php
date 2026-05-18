<?php

namespace App\Http\Controllers\Wiki;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class ModuleWikiController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('wiki/Modules');
    }
}

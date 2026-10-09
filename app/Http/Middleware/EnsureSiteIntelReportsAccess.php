<?php

namespace App\Http\Middleware;

use App\Services\Access\SiteIntelReportAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureSiteIntelReportsAccess
{
    public function __construct(private SiteIntelReportAccess $access) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route()?->getName() === 'site-intel' && $request->query('tab') !== 'reports') {
            return $next($request);
        }

        $user = $request->user();
        if ($user !== null) {
            $this->access->assertAny($user);
        }

        return $next($request);
    }
}

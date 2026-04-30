<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\DashboardFiltersRequest;
use App\Models\User;
use App\Services\Dashboard\UserDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly UserDashboardService $dashboardService,
    ) {
    }

    public function __invoke(DashboardFiltersRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'dashboard' => $this->dashboardService->build($user, $request->filters()),
        ]);
    }
}

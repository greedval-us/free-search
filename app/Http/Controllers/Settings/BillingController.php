<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Access\AccountAccessSummaryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BillingController extends Controller
{
    public function __construct(private readonly AccountAccessSummaryService $summaryService) {}

    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Billing', [
            'access' => $this->summaryService->forUser($request->user()),
            'plans' => config('access.plans', []),
            'reason' => $request->query('reason'),
            'feature' => $request->query('feature'),
        ]);
    }
}

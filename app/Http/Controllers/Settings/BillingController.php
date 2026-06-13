<?php

namespace App\Http\Controllers\Settings;

use App\Exceptions\SubscriptionActivationException;
use App\Http\Controllers\Controller;
use App\Services\Access\AccountAccessSummaryService;
use App\Services\Subscriptions\SubscriptionActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class BillingController extends Controller
{
    public function __construct(
        private readonly AccountAccessSummaryService $summaryService,
        private readonly SubscriptionActivationService $activationService,
    ) {}

    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Billing', [
            'access' => $this->summaryService->forUser($request->user()),
            'plans' => config('access.plans', []),
            'reason' => $request->query('reason'),
            'feature' => $request->query('feature'),
            'tokenStatus' => $request->session()->get('billing_token_status'),
        ]);
    }

    public function activateToken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'activation_token' => ['required', 'string', 'max:64'],
        ]);

        try {
            $this->activationService->activate(
                $request->user(),
                (string) $validated['activation_token'],
            );
        } catch (SubscriptionActivationException $exception) {
            throw ValidationException::withMessages([
                'activation_token' => __($exception->messageKey()),
            ]);
        }

        return to_route('billing.edit')->with('billing_token_status', 'success');
    }
}

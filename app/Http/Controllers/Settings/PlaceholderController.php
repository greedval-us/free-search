<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PlaceholderController extends Controller
{
    public function show(Request $request): Response
    {
        $context = (string) $request->query('context', 'generic');
        $plan = (string) $request->query('plan', '');
        $back = (string) $request->query('back', '/settings/billing');

        if (! in_array($context, ['generic', 'checkout'], true)) {
            $context = 'generic';
        }

        if (! in_array($plan, ['free', 'plus', 'pro', ''], true)) {
            $plan = '';
        }

        if ($back === '' || ! str_starts_with($back, '/')) {
            $back = '/settings/billing';
        }

        return Inertia::render('settings/Placeholder', [
            'placeholder' => [
                'context' => $context,
                'plan' => $plan,
                'back' => $back,
            ],
        ]);
    }
}

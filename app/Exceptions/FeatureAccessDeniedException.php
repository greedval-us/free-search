<?php

namespace App\Exceptions;

use App\Services\Access\DTO\FeatureAccessDecision;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class FeatureAccessDeniedException extends RuntimeException implements ShouldntReport
{
    public function __construct(public readonly FeatureAccessDecision $decision)
    {
        parent::__construct($decision->message ?? __('errors.access.feature_denied'));
    }

    public function render(Request $request): Response
    {
        $request->attributes->set('feature_access_denied', true);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $this->getMessage(),
                'meta' => $this->decision->toMeta(),
            ], $this->decision->limit <= 0 ? Response::HTTP_FORBIDDEN : Response::HTTP_TOO_MANY_REQUESTS);
        }

        return redirect()->route('billing.edit', [
            'feature' => $this->decision->feature,
            'reason' => $this->decision->limit <= 0 ? 'plan' : 'quota',
        ]);
    }
}

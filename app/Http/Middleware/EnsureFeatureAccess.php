<?php

namespace App\Http\Middleware;

use App\Services\Access\Contracts\FeatureAccessRequestResolverInterface;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use App\Services\Access\DTO\FeatureAccessDecision;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class EnsureFeatureAccess
{
    public function __construct(
        private readonly FeatureAccessServiceInterface $featureAccessService,
        private readonly FeatureAccessRequestResolverInterface $requestResolver,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $user = $request->user();

        if (! is_string($routeName) || $user === null) {
            return $next($request);
        }

        $accessRequest = $this->requestResolver->resolve($request);
        if ($accessRequest === null) {
            return $next($request);
        }

        $decision = $accessRequest->consume
            ? $this->featureAccessService->consume($user, $routeName)
            : $this->featureAccessService->inspect($user, $accessRequest->resource, $accessRequest->counts);

        if (! $decision->allowed) {
            return $this->deniedResponse($request, $decision);
        }

        if (! $accessRequest->consume || ! $decision->counts) {
            return $next($request);
        }

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->featureAccessService->refund($user, $routeName);

            throw $exception;
        }

        if (! $response->isSuccessful()) {
            $this->featureAccessService->refund($user, $routeName);
        }

        return $response;
    }

    private function deniedResponse(Request $request, FeatureAccessDecision $decision): Response
    {
        $status = $decision->limit <= 0 ? Response::HTTP_FORBIDDEN : Response::HTTP_TOO_MANY_REQUESTS;
        $message = $decision->message ?? __('errors.access.feature_denied');
        $request->attributes->set('feature_access_denied', true);

        if ($request->expectsJson()) {
            return new JsonResponse([
                'ok' => false,
                'message' => $message,
                'meta' => $decision->toMeta(),
            ], $status);
        }

        return redirect()->route('billing.edit', [
            'feature' => $decision->feature,
            'reason' => $decision->limit <= 0 ? 'plan' : 'quota',
        ]);
    }
}

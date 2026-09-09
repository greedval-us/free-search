<?php

namespace App\Http\Middleware;

use App\Exceptions\FeatureAccessDeniedException;
use App\Services\Access\Contracts\FeatureAccessRequestResolverInterface;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use Closure;
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
            throw new FeatureAccessDeniedException($decision);
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
}

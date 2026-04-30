<?php

namespace App\Http\Middleware;

use App\Models\RequestLog;
use App\Support\Activity\RequestLogSchemaInspector;
use App\Support\Activity\RequestPayloadSanitizer;
use App\Support\Activity\RequestQueryPreviewResolver;
use App\Support\Dashboard\DashboardModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    public function __construct(
        private readonly DashboardModuleRegistry $moduleRegistry,
        private readonly RequestPayloadSanitizer $payloadSanitizer,
        private readonly RequestQueryPreviewResolver $queryPreviewResolver,
        private readonly RequestLogSchemaInspector $schemaInspector,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);

        $this->storeUserActivity($request, $response, $startedAt);

        return $response;
    }

    private function storeUserActivity(Request $request, Response $response, float $startedAt): void
    {
        if ($request->user() === null) {
            return;
        }

        $routeName = $request->route()?->getName();
        if (!is_string($routeName) || $routeName === '') {
            return;
        }

        $moduleKey = $this->moduleRegistry->resolveFromRouteName($routeName);
        if ($moduleKey === null) {
            return;
        }

        $actionKey = $this->resolveActionKey($routeName);
        if ($actionKey === null) {
            return;
        }

        $payload = $this->payloadSanitizer->sanitize($request->all());
        $queryPreview = $this->queryPreviewResolver->resolve($payload);
        if ($queryPreview === null) {
            return;
        }

        $data = [
            'user_id' => $request->user()->id,
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => $routeName,
            'request_data' => $payload,
            'response_data' => null,
            'status_code' => $response->getStatusCode(),
            'response_time' => round((microtime(true) - $startedAt) * 1000, 2),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ];

        if ($this->schemaInspector->hasExtendedSchema()) {
            $data['module_key'] = $moduleKey;
            $data['action_key'] = $actionKey;
            $data['query_preview'] = $queryPreview;
            $data['metadata'] = [
                'route_parameters' => $this->payloadSanitizer->sanitize($request->route()?->parameters() ?? []),
            ];
        }

        RequestLog::query()->create($data);
    }

    private function resolveActionKey(string $routeName): ?string
    {
        if (!str_contains($routeName, '.')) {
            return null;
        }

        $parts = explode('.', $routeName);
        array_shift($parts);

        return implode('.', $parts);
    }
}

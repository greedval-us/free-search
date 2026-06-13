<?php

use App\Http\Middleware\EnsureFeatureAccess;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogUserActivity;
use App\Exceptions\PublicException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'feature.access' => EnsureFeatureAccess::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            EnsureUserIsNotBlocked::class,
            LogUserActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $resolveApiLocale = static function (Request $request): string {
            $locale = strtolower(trim((string) $request->input('locale', app()->getLocale())));

            return in_array($locale, ['ru', 'en'], true) ? $locale : 'en';
        };

        $exceptions->render(function (ValidationException $exception, Request $request) use ($resolveApiLocale): ?JsonResponse {
            if (! $request->expectsJson()) {
                return null;
            }

            app()->setLocale($resolveApiLocale($request));

            return response()->json([
                'ok' => false,
                'message' => __('errors.api.validation'),
                'code' => 'validation_error',
                'errors' => $exception->errors(),
            ], $exception->status);
        });

        $exceptions->render(function (Throwable $exception, Request $request) use ($resolveApiLocale): ?JsonResponse {
            if (! $request->expectsJson()) {
                return null;
            }

            app()->setLocale($resolveApiLocale($request));

            $status = SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR;
            $message = __('errors.api.generic');
            $code = 'internal_error';

            if ($exception instanceof PublicException) {
                $status = $exception->status();
                $message = __($exception->translationKey(), $exception->replace());
                $code = $exception->errorCode();
            } elseif ($exception instanceof AuthenticationException) {
                $status = SymfonyResponse::HTTP_UNAUTHORIZED;
                $message = __('errors.api.unauthorized');
                $code = 'unauthorized';
            } elseif ($exception instanceof AuthorizationException) {
                $status = SymfonyResponse::HTTP_FORBIDDEN;
                $message = __('errors.api.forbidden');
                $code = 'forbidden';
            } elseif ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
                $status = SymfonyResponse::HTTP_NOT_FOUND;
                $message = __('errors.api.not_found');
                $code = 'not_found';
            } elseif ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                [$message, $code] = match ($status) {
                    SymfonyResponse::HTTP_UNAUTHORIZED => [__('errors.api.unauthorized'), 'unauthorized'],
                    SymfonyResponse::HTTP_FORBIDDEN => [__('errors.api.forbidden'), 'forbidden'],
                    SymfonyResponse::HTTP_NOT_FOUND => [__('errors.api.not_found'), 'not_found'],
                    SymfonyResponse::HTTP_TOO_MANY_REQUESTS => [__('errors.api.too_many_requests'), 'too_many_requests'],
                    SymfonyResponse::HTTP_SERVICE_UNAVAILABLE => [__('errors.api.service_unavailable'), 'service_unavailable'],
                    default => [__('errors.api.generic'), 'http_error'],
                };
            }

            return response()->json([
                'ok' => false,
                'message' => $message,
                'code' => $code,
            ], $status);
        });
    })->create();

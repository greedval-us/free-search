<?php

namespace App\Http\Middleware;

use App\Services\Access\AccountAccessSummaryService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(private readonly AccountAccessSummaryService $accessSummaryService) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'seo' => [
                'siteUrl' => $request->getSchemeAndHttpHost(),
                'pages' => config('seo.pages', []),
            ],
            'auth' => [
                'user' => $user?->only([
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'account_type',
                    'created_at',
                ]),
                'access' => $this->accessSummaryService->forUser($user),
                'notifications' => [
                    'unreadCount' => $user?->unreadNotifications()->count() ?? 0,
                    'items' => $user?->notifications()
                        ->latest()
                        ->limit(8)
                        ->get()
                        ->map(fn (DatabaseNotification $notification): array => [
                            'id' => $notification->id,
                            'title' => (string) ($notification->data['title'] ?? class_basename($notification->type)),
                            'body' => (string) ($notification->data['body'] ?? $notification->data['message'] ?? ''),
                            'url' => $notification->data['url'] ?? null,
                            'kind' => (string) ($notification->data['kind'] ?? 'info'),
                            'read_at' => $notification->read_at?->toIso8601String(),
                            'created_at' => $notification->created_at?->toIso8601String(),
                        ])
                        ->values()
                        ->all() ?? [],
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}

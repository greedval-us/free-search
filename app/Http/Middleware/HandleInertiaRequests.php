<?php

namespace App\Http\Middleware;

use App\Services\Access\AccountAccessSummaryService;
use App\Support\Notifications\UserNotificationPresenter;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly AccountAccessSummaryService $accessSummaryService,
        private readonly UserNotificationPresenter $userNotificationPresenter,
    ) {}

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
            'locale' => app()->getLocale(),
            'seo' => [
                'defaultImage' => config('seo.default_image'),
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
                    'items' => $user
                        ? $this->userNotificationPresenter->presentCollection(
                            $user->notifications()->latest()->limit(8)->get()
                        )
                        : [],
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}

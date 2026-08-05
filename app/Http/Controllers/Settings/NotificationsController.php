<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\Notifications\UserNotificationPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class NotificationsController extends Controller
{
    public function __construct(
        private readonly UserNotificationPresenter $userNotificationPresenter,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $periodStart = now()->subMonth();

        $notifications = $user
            ? $user->notifications()
                ->where('created_at', '>=', $periodStart)
                ->latest()
                ->get()
            : collect();

        return Inertia::render('settings/Notifications', [
            'notifications' => $this->userNotificationPresenter->presentCollection($notifications),
            'periodStart' => $periodStart->toIso8601String(),
        ]);
    }
}

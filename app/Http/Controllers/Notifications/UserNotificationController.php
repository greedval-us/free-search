<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()?->unreadNotifications->markAsRead();

        return back(303);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $userNotification = $request->user()?->notifications()
            ->whereKey($notification)
            ->first();

        abort_if($userNotification === null, Response::HTTP_NOT_FOUND);

        if ($userNotification->read_at === null) {
            $userNotification->markAsRead();
        }

        $redirectTo = (string) $request->input('redirect_to', '');

        if ($redirectTo !== '' && str_starts_with($redirectTo, '/')) {
            return redirect()->to($redirectTo, 303);
        }

        return back(303);
    }
}

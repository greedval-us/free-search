<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        if ($this->isSafeLocalRedirect($redirectTo)) {
            return redirect()->to($redirectTo, 303);
        }

        return back(303);
    }

    private function isSafeLocalRedirect(string $url): bool
    {
        if (! str_starts_with($url, '/') || str_starts_with($url, '//') || str_starts_with($url, '/\\')) {
            return false;
        }

        return parse_url($url, PHP_URL_SCHEME) === null
            && parse_url($url, PHP_URL_HOST) === null;
    }
}

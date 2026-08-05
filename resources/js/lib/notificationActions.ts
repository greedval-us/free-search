import { router } from '@inertiajs/vue3';
import { markAllRead as markAllReadAction } from '@/actions/App/Http/Controllers/Notifications/UserNotificationController';
import type { AppNotification } from '@/types';

const notificationVisitOptions = {
    preserveScroll: true,
    preserveState: true,
};

const markReadUrl = (notificationId: string): string =>
    `/notifications/${encodeURIComponent(notificationId)}/read`;

export const submitMarkAllNotificationsRead = (): void => {
    router.post(markAllReadAction().url, {}, notificationVisitOptions);
};

export const submitMarkNotificationRead = (
    notification: AppNotification,
    redirectTo?: string | null
): void => {
    router.post(
        markReadUrl(notification.id),
        {
            redirect_to: redirectTo ?? '',
        },
        notificationVisitOptions
    );
};

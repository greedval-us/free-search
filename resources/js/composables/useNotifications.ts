import { computed, ref, toValue } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import {
    submitMarkAllNotificationsRead,
    submitMarkNotificationRead,
} from '@/lib/notificationActions';
import type { AppNotification, AuthNotifications } from '@/types';

type UseNotificationsOptions = {
    notifications: MaybeRefOrGetter<AuthNotifications | AppNotification[]>;
};

const withLocalReadState = (
    notification: AppNotification,
    locallyReadNotificationIds: Set<string>
): AppNotification => ({
    ...notification,
    read_at:
        notification.read_at ??
        (locallyReadNotificationIds.has(notification.id)
            ? new Date().toISOString()
            : null),
});

export const useNotifications = ({
    notifications,
}: UseNotificationsOptions) => {
    const locallyReadNotificationIds = ref<Set<string>>(new Set());
    const source = computed(() => toValue(notifications));
    const sourceItems = computed<AppNotification[]>(() =>
        Array.isArray(source.value) ? source.value : source.value.items
    );
    const unreadSourceItems = computed(() =>
        sourceItems.value.filter(
            (notification) => notification.read_at === null
        )
    );
    const locallyReadUnreadIds = computed(
        () =>
            new Set(
                unreadSourceItems.value
                    .filter((notification) =>
                        locallyReadNotificationIds.value.has(notification.id)
                    )
                    .map((notification) => notification.id)
            )
    );

    const markNotificationAsReadLocally = (notificationId: string) => {
        locallyReadNotificationIds.value = new Set([
            ...locallyReadNotificationIds.value,
            notificationId,
        ]);
    };

    const items = computed<AppNotification[]>(() => {
        return sourceItems.value.map((notification) =>
            withLocalReadState(notification, locallyReadNotificationIds.value)
        );
    });

    const unreadCount = computed(() => {
        const sourceUnreadCount = Array.isArray(source.value)
            ? unreadSourceItems.value.length
            : source.value.unreadCount;

        return Math.max(0, sourceUnreadCount - locallyReadUnreadIds.value.size);
    });

    const hasUnread = computed(() => unreadCount.value > 0);

    const markAllRead = () => {
        if (!hasUnread.value) {
            return;
        }

        locallyReadNotificationIds.value = new Set(
            unreadSourceItems.value.map((notification) => notification.id)
        );

        submitMarkAllNotificationsRead();
    };

    const markRead = (
        notification: AppNotification,
        redirectTo?: string | null
    ) => {
        if (!notification.read_at) {
            markNotificationAsReadLocally(notification.id);
        }

        submitMarkNotificationRead(notification, redirectTo);
    };

    return {
        items,
        unreadCount,
        hasUnread,
        markAllRead,
        markRead,
    };
};

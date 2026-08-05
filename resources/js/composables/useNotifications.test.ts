import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useNotifications } from '@/composables/useNotifications';
import type { AppNotification, AuthNotifications } from '@/types';

const { submitMarkAllNotificationsRead, submitMarkNotificationRead } =
    vi.hoisted(() => ({
        submitMarkAllNotificationsRead: vi.fn(),
        submitMarkNotificationRead: vi.fn(),
    }));

vi.mock('@/lib/notificationActions', () => ({
    submitMarkAllNotificationsRead,
    submitMarkNotificationRead,
}));

const makeNotification = (
    overrides: Partial<AppNotification> = {}
): AppNotification => ({
    id: 'notification-1',
    title: 'Notification',
    body: 'Body',
    titleKey: null,
    bodyKey: null,
    titleParams: null,
    bodyParams: null,
    url: null,
    kind: 'system',
    read_at: null,
    created_at: '2026-08-05T10:00:00Z',
    ...overrides,
});

describe('useNotifications', () => {
    beforeEach(() => {
        submitMarkAllNotificationsRead.mockReset();
        submitMarkNotificationRead.mockReset();
    });

    it('marks a single unread notification as read optimistically', () => {
        const source = ref<AuthNotifications>({
            unreadCount: 2,
            items: [
                makeNotification({ id: 'unread-1', read_at: null }),
                makeNotification({ id: 'unread-2', read_at: null }),
            ],
        });
        const notifications = useNotifications({ notifications: source });

        notifications.markRead(source.value.items[0], '/workspace');

        expect(notifications.unreadCount.value).toBe(1);
        expect(notifications.items.value[0].read_at).not.toBeNull();
        expect(submitMarkNotificationRead).toHaveBeenCalledWith(
            source.value.items[0],
            '/workspace'
        );
    });

    it('marks all unread notifications as read optimistically', () => {
        const source = ref<AuthNotifications>({
            unreadCount: 2,
            items: [
                makeNotification({ id: 'unread-1', read_at: null }),
                makeNotification({
                    id: 'read-1',
                    read_at: '2026-08-05T09:00:00Z',
                }),
                makeNotification({ id: 'unread-2', read_at: null }),
            ],
        });
        const notifications = useNotifications({ notifications: source });

        notifications.markAllRead();

        expect(notifications.hasUnread.value).toBe(false);
        expect(notifications.unreadCount.value).toBe(0);
        expect(
            notifications.items.value.filter(
                (notification) => !notification.read_at
            )
        ).toHaveLength(0);
        expect(submitMarkAllNotificationsRead).toHaveBeenCalledOnce();
    });

    it('stays in sync when the notification source updates', () => {
        const source = ref<AuthNotifications>({
            unreadCount: 1,
            items: [makeNotification({ id: 'initial-unread', read_at: null })],
        });
        const notifications = useNotifications({ notifications: source });

        notifications.markRead(source.value.items[0]);

        source.value = {
            unreadCount: 1,
            items: [makeNotification({ id: 'server-unread', read_at: null })],
        };

        expect(notifications.unreadCount.value).toBe(1);
        expect(notifications.items.value[0].id).toBe('server-unread');
        expect(notifications.items.value[0].read_at).toBeNull();
    });

    it('does not reduce unread count when marking an already read notification', () => {
        const source = ref<AuthNotifications>({
            unreadCount: 0,
            items: [
                makeNotification({
                    id: 'already-read',
                    read_at: '2026-08-05T08:00:00Z',
                }),
            ],
        });
        const notifications = useNotifications({ notifications: source });

        notifications.markRead(source.value.items[0]);

        expect(notifications.unreadCount.value).toBe(0);
        expect(submitMarkNotificationRead).toHaveBeenCalledWith(
            source.value.items[0],
            undefined
        );
    });
});

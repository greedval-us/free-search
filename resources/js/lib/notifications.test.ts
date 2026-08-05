import { describe, expect, it } from 'vitest';
import {
    resolveNotificationBody,
    resolveNotificationTitle,
} from '@/lib/notifications';
import type { AppNotification } from '@/types';

const makeNotification = (
    overrides: Partial<AppNotification> = {}
): AppNotification => ({
    id: 'notification-1',
    title: 'Fallback title',
    body: 'Fallback body',
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

const translations: Record<string, string> = {
    'notifications.welcome.title': 'Welcome, {name}!',
    'notifications.welcome.body': 'Your plan is {plan}.',
};

const t = (key: string): string => translations[key] ?? key;

describe('notification text resolvers', () => {
    it('uses localized title and interpolates params when titleKey exists', () => {
        const notification = makeNotification({
            titleKey: 'notifications.welcome.title',
            titleParams: { name: 'Greedval' },
        });

        expect(resolveNotificationTitle(notification, t)).toBe(
            'Welcome, Greedval!'
        );
    });

    it('uses localized body and interpolates nullable params', () => {
        const notification = makeNotification({
            bodyKey: 'notifications.welcome.body',
            bodyParams: { plan: 'Pro', extra: null },
        });

        expect(resolveNotificationBody(notification, t)).toBe(
            'Your plan is Pro.'
        );
    });

    it('falls back to stored title and body when localization keys are absent', () => {
        const notification = makeNotification({
            title: 'Stored title',
            body: 'Stored body',
        });

        expect(resolveNotificationTitle(notification, t)).toBe('Stored title');
        expect(resolveNotificationBody(notification, t)).toBe('Stored body');
    });
});

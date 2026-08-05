import type { AppNotification } from '@/types';

type TranslateFn = (key: string) => string;

const interpolate = (
    template: string,
    params?: Record<string, string | number | null> | null
): string => {
    if (!params) {
        return template;
    }

    return Object.entries(params).reduce((message, [key, value]) => {
        return message.replaceAll(`{${key}}`, String(value ?? ''));
    }, template);
};

export const resolveNotificationTitle = (
    notification: AppNotification,
    t: TranslateFn
): string => {
    if (notification.titleKey) {
        return interpolate(t(notification.titleKey), notification.titleParams);
    }

    return notification.title;
};

export const resolveNotificationBody = (
    notification: AppNotification,
    t: TranslateFn
): string => {
    if (notification.bodyKey) {
        return interpolate(t(notification.bodyKey), notification.bodyParams);
    }

    return notification.body;
};

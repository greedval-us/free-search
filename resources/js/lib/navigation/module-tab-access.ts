import type { FeatureAccess } from '@/types';

type AccessibleTab = {
    key: string;
    accessKey?: string;
    accessKeys?: readonly string[];
};

export const blockedTabFeature = (
    tab: AccessibleTab,
    features?: Record<string, Pick<FeatureAccess, 'limit'>>
): string | null => {
    const keys = tab.accessKeys?.length
        ? tab.accessKeys
        : [tab.accessKey ?? tab.key];
    const denied = keys.every((key) => {
        const access = features?.[key];

        return access !== undefined && access.limit <= 0;
    });

    return denied ? keys[0] : null;
};

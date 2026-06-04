import type { Component } from 'vue';
import { MODULE_TAB_ICONS } from '@/lib/navigation/tab-icons';
import BlueskyAnalyticsTab from './tabs/BlueskyAnalyticsTab.vue';
import BlueskySearchTab from './tabs/BlueskySearchTab.vue';

export type BlueskyTabValue = 'search';

export type BlueskyTabDefinition = {
    key: BlueskyTabValue;
    labelKey: string;
    icon: Component;
    component: Component;
    accessKey?: string;
};

export const BLUESKY_TABS: readonly BlueskyTabDefinition[] = [
    {
        key: 'search',
        labelKey: 'bluesky.tabs.search',
        icon: MODULE_TAB_ICONS.search,
        component: BlueskySearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'bluesky.tabs.analytics',
        icon: MODULE_TAB_ICONS.analytics,
        component: BlueskyAnalyticsTab,
        accessKey: 'bluesky.analytics',
    },
] as const;

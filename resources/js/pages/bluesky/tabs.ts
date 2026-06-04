import type { Component } from 'vue';
import { MODULE_TAB_ICONS } from '@/lib/navigation/tab-icons';
import BlueskyAnalyticsTab from './tabs/BlueskyAnalyticsTab.vue';
import BlueskyParserTab from './tabs/BlueskyParserTab.vue';
import BlueskySearchTab from './tabs/BlueskySearchTab.vue';

export type BlueskyTabValue = 'search' | 'analytics' | 'parser';

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
    {
        key: 'parser',
        labelKey: 'bluesky.tabs.parser',
        icon: MODULE_TAB_ICONS.parser,
        component: BlueskyParserTab,
        accessKey: 'bluesky.parser',
    },
] as const;

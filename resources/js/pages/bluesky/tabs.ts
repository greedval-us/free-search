import type { Component } from 'vue';
import { createModuleTabs } from '@/lib/navigation/create-module-tabs';
import BlueskyAnalyticsTab from './tabs/BlueskyAnalyticsTab.vue';
import BlueskyParserTab from './tabs/BlueskyParserTab.vue';
import BlueskyReportsTab from './tabs/BlueskyReportsTab.vue';
import BlueskySearchTab from './tabs/BlueskySearchTab.vue';

export type BlueskyTabValue = 'search' | 'analytics' | 'parser' | 'reports';

export type BlueskyTabDefinition = {
    key: BlueskyTabValue;
    labelKey: string;
    icon: Component;
    component: Component;
    accessKey?: string;
};

export const BLUESKY_TABS: readonly BlueskyTabDefinition[] = createModuleTabs([
    {
        key: 'search',
        labelKey: 'bluesky.tabs.search',
        component: BlueskySearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'bluesky.tabs.analytics',
        component: BlueskyAnalyticsTab,
        accessKey: 'bluesky.analytics',
    },
    {
        key: 'parser',
        labelKey: 'bluesky.tabs.parser',
        component: BlueskyParserTab,
        accessKey: 'bluesky.parser',
    },
    {
        key: 'reports',
        labelKey: 'bluesky.tabs.reports',
        component: BlueskyReportsTab,
        accessKey: 'bluesky.analytics',
    },
]) as readonly BlueskyTabDefinition[];

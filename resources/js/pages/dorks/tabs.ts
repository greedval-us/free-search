import { BarChart3, Search } from 'lucide-vue-next';
import type { Component } from 'vue';
import DorksAnalyticsTab from './tabs/DorksAnalyticsTab.vue';
import DorksSearchTab from './tabs/DorksSearchTab.vue';
import type { DorksTabValue } from './types';

export type DorksTabDefinition = {
    key: DorksTabValue;
    labelKey: string;
    icon: Component;
    component: Component;
};

export const DORKS_TABS: DorksTabDefinition[] = [
    {
        key: 'search',
        labelKey: 'dorks.tabs.search',
        icon: Search,
        component: DorksSearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'dorks.tabs.analytics',
        icon: BarChart3,
        component: DorksAnalyticsTab,
    },
];


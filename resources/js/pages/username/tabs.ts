import type { Component } from 'vue';
import { MODULE_TAB_ICONS } from '@/lib/navigation/tab-icons';
import UsernameAnalyticsTab from './tabs/UsernameAnalyticsTab.vue';
import UsernameSearchTab from './tabs/UsernameSearchTab.vue';
import type { UsernameTabValue } from './types';

export type UsernameTabDefinition = {
    key: UsernameTabValue;
    labelKey: string;
    icon: Component;
    component: Component;
};

export const USERNAME_TABS: readonly UsernameTabDefinition[] = [
    {
        key: 'search',
        labelKey: 'username.tabs.search',
        icon: MODULE_TAB_ICONS.search,
        component: UsernameSearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'username.tabs.analytics',
        icon: MODULE_TAB_ICONS.analytics,
        component: UsernameAnalyticsTab,
    },
] as const;

import { Search } from 'lucide-vue-next';
import type { Component } from 'vue';
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
        icon: Search,
        component: UsernameSearchTab,
    },
] as const;

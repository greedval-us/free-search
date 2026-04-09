import { BarChart3, Search } from 'lucide-vue-next';
import type { Component } from 'vue';
import TelegramAnalyticsTab from './tabs/TelegramAnalyticsTab.vue';
import TelegramSearchTab from './tabs/TelegramSearchTab.vue';
import type { TelegramTabValue } from './types';

export type TelegramTabDefinition = {
    key: TelegramTabValue;
    labelKey: string;
    icon: Component;
    component: Component;
};

export const TELEGRAM_TABS: readonly TelegramTabDefinition[] = [
    {
        key: 'search',
        labelKey: 'telegram.tabs.search',
        icon: Search,
        component: TelegramSearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'telegram.tabs.analytics',
        icon: BarChart3,
        component: TelegramAnalyticsTab,
    },
] as const;

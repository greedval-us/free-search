import { BarChart3, Search, Wrench } from 'lucide-vue-next';
import type { Component } from 'vue';
import TelegramAnalyticsTab from './tabs/TelegramAnalyticsTab.vue';
import TelegramParserTab from './tabs/TelegramParserTab.vue';
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
    {
        key: 'parser',
        labelKey: 'telegram.tabs.parser',
        icon: Wrench,
        component: TelegramParserTab,
    },
] as const;

import type { Component } from 'vue';
import { MODULE_TAB_ICONS } from '@/lib/navigation/tab-icons';
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
        icon: MODULE_TAB_ICONS.search,
        component: TelegramSearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'telegram.tabs.analytics',
        icon: MODULE_TAB_ICONS.analytics,
        component: TelegramAnalyticsTab,
    },
    {
        key: 'parser',
        labelKey: 'telegram.tabs.parser',
        icon: MODULE_TAB_ICONS.parser,
        component: TelegramParserTab,
    },
] as const;

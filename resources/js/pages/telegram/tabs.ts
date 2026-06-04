import type { Component } from 'vue';
import { createModuleTabs } from '@/lib/navigation/create-module-tabs';
import TelegramAnalyticsTab from './tabs/TelegramAnalyticsTab.vue';
import TelegramParserTab from './tabs/TelegramParserTab.vue';
import TelegramSearchTab from './tabs/TelegramSearchTab.vue';
import type { TelegramTabValue } from './types';

export type TelegramTabDefinition = {
    key: TelegramTabValue;
    labelKey: string;
    icon: Component;
    component: Component;
    accessKey?: string;
};

export const TELEGRAM_TABS: readonly TelegramTabDefinition[] = createModuleTabs([
    {
        key: 'search',
        labelKey: 'telegram.tabs.search',
        component: TelegramSearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'telegram.tabs.analytics',
        component: TelegramAnalyticsTab,
        accessKey: 'telegram.analytics',
    },
    {
        key: 'parser',
        labelKey: 'telegram.tabs.parser',
        component: TelegramParserTab,
        accessKey: 'telegram.parser',
    },
]) as readonly TelegramTabDefinition[];

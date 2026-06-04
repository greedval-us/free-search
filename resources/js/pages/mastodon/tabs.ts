import type { Component } from 'vue';
import { createModuleTabs } from '@/lib/navigation/create-module-tabs';
import MastodonAnalyticsTab from './tabs/MastodonAnalyticsTab.vue';
import MastodonParserTab from './tabs/MastodonParserTab.vue';
import MastodonSearchTab from './tabs/MastodonSearchTab.vue';
import type { MastodonTabValue } from './types';

export type MastodonTabDefinition = {
    key: MastodonTabValue;
    labelKey: string;
    icon: Component;
    component: Component;
    accessKey?: string;
};

export const MASTODON_TABS: readonly MastodonTabDefinition[] = createModuleTabs([
    {
        key: 'search',
        labelKey: 'mastodon.tabs.search',
        component: MastodonSearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'mastodon.tabs.analytics',
        component: MastodonAnalyticsTab,
        accessKey: 'mastodon.analytics',
    },
    {
        key: 'parser',
        labelKey: 'mastodon.tabs.parser',
        component: MastodonParserTab,
        accessKey: 'mastodon.parser',
    },
]) as readonly MastodonTabDefinition[];

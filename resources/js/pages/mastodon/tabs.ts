import type { Component } from 'vue';
import { MODULE_TAB_ICONS } from '@/lib/navigation/tab-icons';
import MastodonSearchTab from './tabs/MastodonSearchTab.vue';
import type { MastodonTabValue } from './types';

export type MastodonTabDefinition = {
    key: MastodonTabValue;
    labelKey: string;
    icon: Component;
    component: Component;
};

export const MASTODON_TABS: readonly MastodonTabDefinition[] = [
    {
        key: 'search',
        labelKey: 'mastodon.tabs.search',
        icon: MODULE_TAB_ICONS.search,
        component: MastodonSearchTab,
    },
] as const;

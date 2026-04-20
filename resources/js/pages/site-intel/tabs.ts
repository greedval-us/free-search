import { Activity, Globe } from 'lucide-vue-next';
import type { Component } from 'vue';
import DomainLiteTab from './tabs/DomainLiteTab.vue';
import SiteHealthTab from './tabs/SiteHealthTab.vue';
import type { SiteIntelTabValue } from './types';

export type SiteIntelTabDefinition = {
    key: SiteIntelTabValue;
    labelKey: string;
    icon: Component;
    component: Component;
};

export const SITE_INTEL_TABS: readonly SiteIntelTabDefinition[] = [
    {
        key: 'siteHealth',
        labelKey: 'siteIntel.tabs.siteHealth',
        icon: Activity,
        component: SiteHealthTab,
    },
    {
        key: 'domainLite',
        labelKey: 'siteIntel.tabs.domainLite',
        icon: Globe,
        component: DomainLiteTab,
    },
] as const;


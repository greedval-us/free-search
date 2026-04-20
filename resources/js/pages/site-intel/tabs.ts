import { Activity, BarChart3, Globe } from 'lucide-vue-next';
import type { Component } from 'vue';
import DomainLiteTab from './tabs/DomainLiteTab.vue';
import SiteIntelAnalyticsTab from './tabs/SiteIntelAnalyticsTab.vue';
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
    {
        key: 'analytics',
        labelKey: 'siteIntel.tabs.analytics',
        icon: BarChart3,
        component: SiteIntelAnalyticsTab,
    },
] as const;

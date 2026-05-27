import { Activity, BarChart3, Globe, SearchCheck } from 'lucide-vue-next';
import type { Component } from 'vue';
import DomainLiteTab from './tabs/DomainLiteTab.vue';
import SeoAuditTab from './tabs/SeoAuditTab.vue';
import SiteHealthTab from './tabs/SiteHealthTab.vue';
import SiteIntelAnalyticsTab from './tabs/SiteIntelAnalyticsTab.vue';
import type { SiteIntelTabValue } from './types';

export type SiteIntelTabDefinition = {
    key: SiteIntelTabValue;
    labelKey: string;
    icon: Component;
    component: Component;
    accessKey?: string;
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
        accessKey: 'site-intel.analytics',
    },
    {
        key: 'seoAudit',
        labelKey: 'siteIntel.tabs.seoAudit',
        icon: SearchCheck,
        component: SeoAuditTab,
        accessKey: 'site-intel.seo-audit',
    },
] as const;

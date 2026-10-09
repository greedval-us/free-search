import { BarChart3, FileClock, Newspaper } from 'lucide-vue-next';
import NewsAnalyticsTab from './tabs/NewsAnalyticsTab.vue';
import NewsReportsTab from './tabs/NewsReportsTab.vue';
import NewsSearchTab from './tabs/NewsSearchTab.vue';

export const NEWS_MEDIA_TABS = [
    {
        key: 'search',
        labelKey: 'newsMediaIntel.tabs.search',
        icon: Newspaper,
        component: NewsSearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'newsMediaIntel.tabs.analytics',
        icon: BarChart3,
        component: NewsAnalyticsTab,
    },
    {
        key: 'reports',
        labelKey: 'newsMediaReports.tab',
        icon: FileClock,
        component: NewsReportsTab,
    },
] as const;

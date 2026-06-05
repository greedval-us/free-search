import { createModuleTabs } from '@/lib/navigation/create-module-tabs';
import YouTubeAnalyticsTab from './tabs/YouTubeAnalyticsTab.vue';
import YouTubeParserTab from './tabs/YouTubeParserTab.vue';
import YouTubeSearchTab from './tabs/YouTubeSearchTab.vue';

export const YOUTUBE_TABS = createModuleTabs([
    {
        key: 'search',
        labelKey: 'youtube.tabs.search',
        component: YouTubeSearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'youtube.tabs.analytics',
        component: YouTubeAnalyticsTab,
        accessKey: 'youtube.analytics',
    },
    {
        key: 'parser',
        labelKey: 'youtube.tabs.parser',
        component: YouTubeParserTab,
        accessKey: 'youtube.parser',
    },
]);

import { MODULE_TAB_ICONS } from '@/lib/navigation/tab-icons';
import YouTubeAnalyticsTab from './tabs/YouTubeAnalyticsTab.vue';
import YouTubeParserTab from './tabs/YouTubeParserTab.vue';
import YouTubeSearchTab from './tabs/YouTubeSearchTab.vue';

export const YOUTUBE_TABS = [
    {
        key: 'search',
        labelKey: 'youtube.tabs.search',
        icon: MODULE_TAB_ICONS.search,
        component: YouTubeSearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'youtube.tabs.analytics',
        icon: MODULE_TAB_ICONS.analytics,
        component: YouTubeAnalyticsTab,
        accessKey: 'youtube.analytics',
    },
    {
        key: 'parser',
        labelKey: 'youtube.tabs.parser',
        icon: MODULE_TAB_ICONS.parser,
        component: YouTubeParserTab,
        accessKey: 'youtube.parser',
    },
] as const;

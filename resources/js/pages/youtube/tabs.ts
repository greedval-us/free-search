import { BarChart3, MessageSquareText, Search } from 'lucide-vue-next';
import YouTubeAnalyticsTab from './tabs/YouTubeAnalyticsTab.vue';
import YouTubeParserTab from './tabs/YouTubeParserTab.vue';
import YouTubeSearchTab from './tabs/YouTubeSearchTab.vue';

export const YOUTUBE_TABS = [
    {
        key: 'search',
        labelKey: 'youtube.tabs.search',
        icon: Search,
        component: YouTubeSearchTab,
    },
    {
        key: 'analytics',
        labelKey: 'youtube.tabs.analytics',
        icon: BarChart3,
        component: YouTubeAnalyticsTab,
    },
    {
        key: 'parser',
        labelKey: 'youtube.tabs.parser',
        icon: MessageSquareText,
        component: YouTubeParserTab,
    },
] as const;

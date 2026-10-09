import { BarChart3, FileClock, Radar, Search, Wrench } from 'lucide-vue-next';

export const MODULE_TAB_ICONS = {
    analytics: BarChart3,
    parser: Wrench,
    search: Search,
    tracking: Radar,
    reports: FileClock,
} as const;

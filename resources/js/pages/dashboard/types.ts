export interface Summary {
    total_actions: number;
    actions_last_7_days: number;
    actions_last_30_days: number;
    active_days_last_30_days: number;
}

export interface FavoriteModule {
    key: string;
    total: number;
}

export interface ModuleCard {
    key: string;
    count: number;
    last_at: string | null;
    url: string;
    is_pinned: boolean;
}

export interface ActivityFeedRow {
    request_log_id: number;
    module_key: string;
    query_preview: string;
    at: string | null;
    run_url: string | null;
}

export interface SavedQueryRow {
    id: number;
    module_key: string;
    query_preview: string;
    run_url: string | null;
    last_used_at: string | null;
    created_at: string | null;
}

export interface ChartRow {
    date: string;
    day: string;
    count: number;
}

export interface DashboardFilters {
    module_key: string;
    query: string;
    period: string;
    date_from: string;
    date_to: string;
}

export interface DashboardPayload {
    summary: Summary;
    favorite_module: FavoriteModule | null;
    modules: ModuleCard[];
    activity_feed: ActivityFeedRow[];
    chart: ChartRow[];
    saved_queries: SavedQueryRow[];
    pinned_modules: string[];
    filters: DashboardFilters;
    available_modules: string[];
}

export const defaultDashboardFilters = (): DashboardFilters => ({
    module_key: '',
    query: '',
    period: '30d',
    date_from: '',
    date_to: '',
});

export const emptyDashboard = (): DashboardPayload => ({
    summary: {
        total_actions: 0,
        actions_last_7_days: 0,
        actions_last_30_days: 0,
        active_days_last_30_days: 0,
    },
    favorite_module: null,
    modules: [],
    activity_feed: [],
    chart: [],
    saved_queries: [],
    pinned_modules: [],
    filters: defaultDashboardFilters(),
    available_modules: [
        'bluesky',
        'site-intel',
        'telegram',
        'youtube',
        'shifr',
    ],
});

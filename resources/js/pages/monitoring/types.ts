export const platforms = [
    'telegram',
    'youtube',
    'bluesky',
    'mastodon',
    'news',
] as const;
export const periods = ['day', 'three_days', 'week', 'month'] as const;
export type Period = (typeof periods)[number];
export type Platform = (typeof platforms)[number];
export type Schedule = {
    id: number;
    period: Period;
    enabled: boolean;
    time: string;
    timezone: string;
    anchor_date: string | null;
    next_run_at: string | null;
    delivery_enabled: boolean;
};
export type Source = {
    id: number;
    platform: Platform;
    input: string;
    identity: string | null;
    title: string | null;
    status: string;
    last_collected_at: string | null;
    next_collect_at: string | null;
    coverage_start: string | null;
    coverage_end: string | null;
    error: string | null;
    warnings: unknown[];
};
export type ProjectSettings = {
    name: string;
    mode: 'overview' | 'topic';
    filters: { include: string[]; exclude: string[]; author: string | null };
    language: 'ru' | 'en';
    timezone: string;
    delivery_enabled: boolean;
    attach_files: boolean;
    empty_delivery: 'send' | 'skip';
    collection_enabled: boolean;
    collect_interval_minutes: number;
};
export type Project = ProjectSettings & {
    id: number;
    status: string;
    sources: Source[];
    schedules: Schedule[];
    sources_count?: number;
    reports_count?: number;
};
export type Report = {
    id: number;
    project_id: number;
    project_name: string | null;
    version: number;
    status: string;
    period: Period;
    start_at: string;
    end_at: string;
    cutoff_at: string;
    timezone: string;
    created_at: string;
    completed_at: string | null;
    expires_at: string | null;
    expired: boolean;
    delivery_status: string;
    file_status: string;
    summary: {
        title?: string;
        introduction?: string;
        message?: string;
        count?: number;
        unknown_date_count?: number;
        truncated?: boolean;
        platform_counts?: Partial<Record<Platform, number>>;
        group_count?: number;
        recurring_topics?: {
            term: string;
            count: number;
            material_ids: number[];
            urls: string[];
        }[];
        timeline?: Record<string, number>;
        bullets?: {
            text: string;
            urls: string[];
            count?: number;
            platforms?: Platform[];
            first_published_at?: string | null;
            last_published_at?: string | null;
        }[];
        comparison?: {
            available: boolean;
            reason?: string;
            difference?: number;
            previous_report_id?: number;
        };
    };
    coverage: {
        id: number;
        platform: Platform;
        title: string | null;
        identity: string | null;
        state: string;
        complete: boolean;
        gaps: { start: string; end: string }[];
        warnings: string[];
    }[];
};
export type Material = {
    id?: number;
    platform: Platform;
    title: string | null;
    text: string | null;
    author: string | null;
    published_at: string | null;
    url: string;
    metrics?: Record<string, unknown>;
};
export type Pagination<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
export type ProjectState = {
    project: Project;
    reports: Report[];
    botLinked: boolean;
    limits: {
        min_interval_minutes: number;
        sources: number;
        active_projects: number;
        retention_days: number;
    };
};

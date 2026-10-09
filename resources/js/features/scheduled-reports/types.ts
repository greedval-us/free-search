import type { RouteQueryOptions } from '@/wayfinder';

export type ReportInterval = '1' | '3' | '7' | 'month';
export type ReportStatus = 'pending' | 'processing' | 'completed' | 'failed';
export type ReportFormat = 'html' | 'json';
export type ScheduleFields = {
    name: string;
    interval: ReportInterval;
    sendTime: string;
    timezone: string;
    sendToBot: boolean;
};
export type ScheduleIdentity = { id: number; enabled: boolean };
export type PersistedSchedule = ScheduleFields &
    ScheduleIdentity & {
        nextRunAt: string | null;
        createdAt: string;
    };
export type SavedReport = {
    id: number;
    scheduleId: number | null;
    scheduleName: string | null;
    scheduledFor: string;
    status: ReportStatus;
    errorCode: string | null;
    errorMessage?: string | null;
    completedAt: string | null;
};
export type ReportHistory<TReport extends SavedReport = SavedReport> = {
    data: TReport[];
    currentPage: number;
    lastPage: number;
    total: number;
    perPage: number;
};
export type ReportsList<
    TSchedule extends ScheduleIdentity,
    TReport extends SavedReport,
> = {
    schedules: TSchedule[];
    reports: ReportHistory<TReport>;
    botLinked: boolean;
    botExportsEnabled: boolean;
    maxSchedules: number;
    timezone: string;
};
export type ReportRoutes = {
    index: { url: (options?: RouteQueryOptions) => string };
    store: { url: (options?: RouteQueryOptions) => string };
    change: { url: (id: number, options?: RouteQueryOptions) => string };
    runNow: { url: (id: number, options?: RouteQueryOptions) => string };
    destroy: { url: (id: number, options?: RouteQueryOptions) => string };
    view: { url: (id: number, options?: RouteQueryOptions) => string };
    download: {
        url: (
            parameters: { report: number; format: ReportFormat },
            options?: RouteQueryOptions
        ) => string;
    };
};

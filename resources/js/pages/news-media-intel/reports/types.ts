import type { NewsFilters } from '../types';

export type ReportInterval = '1' | '3' | '7' | 'month';
export type ReportScheduleForm = {
    name: string;
    queries: string;
    brand: string;
    competitors: string;
    domain: string;
    filters: NewsFilters;
    interval: ReportInterval;
    sendTime: string;
    timezone: string;
    sendToBot: boolean;
};
export type ReportSchedule = {
    id: number;
    name: string;
    queries: string[];
    brand: string | null;
    competitors: string[];
    domain: string | null;
    searchOptions: NewsFilters;
    interval: ReportInterval;
    sendTime: string;
    timezone: string;
    sendToBot: boolean;
    enabled: boolean;
    nextRunAt: string | null;
    createdAt: string;
};
export type SavedReport = {
    id: number;
    scheduleId: number | null;
    scheduleName: string | null;
    query: string;
    scheduledFor: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    errorCode: string | null;
    errorMessage: string | null;
    completedAt: string | null;
};
export type ReportHistory = {
    data: SavedReport[];
    currentPage: number;
    lastPage: number;
    total: number;
    perPage: number;
};
export type ReportsList = {
    schedules: ReportSchedule[];
    reports: ReportHistory;
    botLinked: boolean;
    botExportsEnabled: boolean;
    timezone: string;
    maxQueries: number;
    maxSchedules: number;
};

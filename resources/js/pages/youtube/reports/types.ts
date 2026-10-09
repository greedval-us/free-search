import type {
    ScheduleFields,
    PersistedSchedule,
    SavedReport as BaseSavedReport,
    ReportHistory as BaseReportHistory,
    ReportsList as BaseReportsList,
} from '@/features/scheduled-reports/types';
export type { ReportInterval } from '@/features/scheduled-reports/types';

export type ReportScheduleForm = ScheduleFields & { channels: string };
export type ReportSchedule = PersistedSchedule & { channels: string[] };
export type AnalyticsReport = BaseSavedReport & {
    channelInput: string;
    dateFrom: string;
    dateTo: string;
};
export type ReportHistory = BaseReportHistory<AnalyticsReport>;
export type ReportsList = BaseReportsList<ReportSchedule, AnalyticsReport> & {
    maxChannels: number;
};

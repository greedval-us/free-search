import type {
    ScheduleFields,
    PersistedSchedule,
    SavedReport as BaseSavedReport,
    ReportHistory as BaseReportHistory,
    ReportsList as BaseReportsList,
} from '@/features/scheduled-reports/types';
export type { ReportInterval } from '@/features/scheduled-reports/types';
import type { NewsFilters } from '../types';
export type ReportScheduleForm = ScheduleFields & {
    queries: string;
    brand: string;
    competitors: string;
    domain: string;
    filters: NewsFilters;
};
export type ReportSchedule = PersistedSchedule & {
    queries: string[];
    brand: string | null;
    competitors: string[];
    domain: string | null;
    searchOptions: NewsFilters;
};
export type SavedReport = BaseSavedReport & { query: string };
export type ReportHistory = BaseReportHistory<SavedReport>;
export type ReportsList = BaseReportsList<ReportSchedule, SavedReport> & {
    maxQueries: number;
};

import type {
    ScheduleFields,
    PersistedSchedule,
    SavedReport as BaseSavedReport,
    ReportHistory as BaseReportHistory,
    ReportsList as BaseReportsList,
} from '@/features/scheduled-reports/types';
import type { SitePlatformType } from '../lib/seo-audit-options';
export type { SitePlatformType } from '../lib/seo-audit-options';
export type { ReportInterval } from '@/features/scheduled-reports/types';
export type SiteReportType = 'analytics' | 'seo-audit';
export type ReportScheduleForm = ScheduleFields & {
    targets: string;
    reportType: SiteReportType;
    crawlLimit: number;
    platformType: SitePlatformType;
};
export type ReportSchedule = PersistedSchedule & {
    targets: string[];
    reportType: SiteReportType;
    crawlLimit: number;
    platformType: SitePlatformType;
};
export type SiteReport = BaseSavedReport & {
    targetUrl: string;
    reportType: SiteReportType;
};
export type ReportHistory = BaseReportHistory<SiteReport>;
export type ReportsList = BaseReportsList<ReportSchedule, SiteReport> & {
    maxTargets: number;
    availableReportTypes: SiteReportType[];
};

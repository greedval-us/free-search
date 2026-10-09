export type ReportInterval = '1' | '3' | '7' | 'month';
export type SiteReportType = 'analytics' | 'seo-audit';
export type SitePlatformType =
    | 'auto'
    | 'generic'
    | 'media-platform'
    | 'content-site'
    | 'storefront';

export type ReportScheduleForm = {
    name: string;
    targets: string;
    reportType: SiteReportType;
    crawlLimit: number;
    platformType: SitePlatformType;
    interval: ReportInterval;
    sendTime: string;
    timezone: string;
    sendToBot: boolean;
};

export type ReportSchedule = Omit<ReportScheduleForm, 'targets'> & {
    id: number;
    targets: string[];
    enabled: boolean;
    nextRunAt: string | null;
    createdAt: string;
};

export type SiteReport = {
    id: number;
    scheduleId: number | null;
    scheduleName: string | null;
    targetUrl: string;
    reportType: SiteReportType;
    scheduledFor: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    errorCode: string | null;
    errorMessage?: string | null;
    completedAt: string | null;
};

export type ReportHistory = {
    data: SiteReport[];
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
    maxTargets: number;
    maxSchedules: number;
    timezone: string;
    availableReportTypes: SiteReportType[];
};

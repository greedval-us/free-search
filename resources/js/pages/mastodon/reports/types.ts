export type ReportInterval = '1' | '3' | '7' | 'month';

export type ReportScheduleForm = {
    name: string;
    accounts: string;
    interval: ReportInterval;
    sendTime: string;
    timezone: string;
    sendToBot: boolean;
};

export type ReportSchedule = Omit<ReportScheduleForm, 'accounts'> & {
    id: number;
    accounts: string[];
    enabled: boolean;
    nextRunAt: string | null;
    createdAt: string;
};

export type AnalyticsReport = {
    id: number;
    scheduleId: number | null;
    scheduleName: string | null;
    accountInput: string;
    scheduledFor: string;
    dateFrom: string;
    dateTo: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    errorCode: string | null;
    errorMessage?: string | null;
    completedAt: string | null;
};

export type ReportHistory = {
    data: AnalyticsReport[];
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
    maxAccounts: number;
    maxSchedules: number;
    timezone: string;
};

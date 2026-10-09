import type { ReportInterval, ScheduleFields } from './types';

export const REPORT_INTERVALS: readonly ReportInterval[] = [
    '1',
    '3',
    '7',
    'month',
];
export const REPORT_POLL_INTERVAL_MS = 60_000;
export const REPORT_NAME_MAX_LENGTH = 100;
export const DEFAULT_REPORT_INTERVAL: ReportInterval = '7';
export const DEFAULT_REPORT_SEND_TIME = '09:00';

export const initialScheduleFields = (): ScheduleFields => ({
    name: '',
    interval: DEFAULT_REPORT_INTERVAL,
    sendTime: DEFAULT_REPORT_SEND_TIME,
    timezone: '',
    sendToBot: false,
});

export const validScheduleTiming = (form: ScheduleFields): boolean =>
    form.name.trim().length > 0 &&
    form.name.trim().length <= REPORT_NAME_MAX_LENGTH &&
    REPORT_INTERVALS.includes(form.interval) &&
    /^(?:[01]\d|2[0-3]):[0-5]\d$/u.test(form.sendTime) &&
    form.timezone.trim().length > 0;

export const reportInputLines = (value: string): string[] =>
    value
        .split(/\r\n?|\n/u)
        .map((line) => line.trim())
        .filter(Boolean);

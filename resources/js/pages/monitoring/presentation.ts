import type { Period, Report } from './types';

export const reportNeedsPolling = (
    report: Pick<
        Report,
        'status' | 'file_status' | 'delivery_status' | 'expired'
    >
): boolean => {
    if (report.expired) {
        return false;
    }

    if (['queued', 'building'].includes(report.status)) {
        return true;
    }

    return (
        ['completed', 'partial', 'empty'].includes(report.status) &&
        (['pending', 'working'].includes(report.file_status) ||
            (report.file_status === 'ready' &&
                ['pending', 'queued'].includes(report.delivery_status)))
    );
};

export const sourceIssue = (
    value: unknown,
    t: (key: string) => string
): string => {
    const key = `monitoring.sourceIssue.${String(value)}`;
    const translated = t(key);

    return translated === key
        ? t('monitoring.sourceUnavailableHint')
        : translated;
};

export const scheduleDescription = (
    period: Period,
    time: string,
    timezone: string,
    anchor: string | null,
    t: (key: string, values?: Record<string, string | number>) => string
) =>
    t(`monitoring.schedule.${period}`, {
        time: time.slice(0, 5),
        timezone,
        anchor: anchor?.slice(0, 10) ?? '—',
    });

export const sourceUrl = (value: unknown): string | undefined => {
    if (typeof value !== 'string') {
        return;
    }

    try {
        const url = new URL(value);

        if (
            ['http:', 'https:'].includes(url.protocol) &&
            !url.username &&
            !url.password
        ) {
            return url.href;
        }
    } catch {
        /* A missing source URL is rendered as plain text. */
    }
};

export const displayDate = (
    value: string | null,
    timezone = 'UTC',
    language = 'en'
) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (!Number.isFinite(date.getTime())) {
        return '—';
    }

    try {
        return new Intl.DateTimeFormat(language, {
            dateStyle: 'medium',
            timeStyle: 'short',
            timeZone: timezone,
        }).format(date);
    } catch {
        return date.toISOString();
    }
};

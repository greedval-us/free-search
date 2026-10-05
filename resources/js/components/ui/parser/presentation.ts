import type { ParserRunStatus } from '@/composables/useParserRun';

export type ParserDisplayStatus = ParserRunStatus | 'idle' | 'unknown';

export const normalizeParserStatus = (
    status: string | null | undefined
): ParserDisplayStatus => {
    switch (status) {
        case 'idle':
        case 'running':
        case 'completed':
        case 'failed':
        case 'stopped':
            return status;

        default:
            return 'unknown';
    }
};

export const parserProgressStatus = (
    stage: string | undefined,
    active: boolean
): ParserDisplayStatus => {
    if (active) {
        return 'running';
    }

    return normalizeParserStatus(stage);
};

export const boundedProgress = (progress: number): number =>
    Number.isFinite(progress) ? Math.max(0, Math.min(100, progress)) : 0;

export const isPartialParserExport = (item: {
    status: string;
    downloadable: boolean;
}): boolean =>
    item.downloadable &&
    (item.status === 'failed' || item.status === 'stopped');

export const formatParserDateTime = (
    value: string | null,
    locale: string
): string | null => {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return new Intl.DateTimeFormat(locale, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};
